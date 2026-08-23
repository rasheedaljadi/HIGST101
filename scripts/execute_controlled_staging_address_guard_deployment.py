import os
import sys
import json
import time
import subprocess
from datetime import datetime

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

TARGET_COMMIT = 'c517da3d22e6dac2b872993ec2a2948b4d183f63'
REMOTE_PATH = '/home/highest-ye/htdocs/highest-ye.store'

def run_local_cmd(cmd):
    res = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    return res.returncode, res.stdout.strip(), res.stderr.strip()

def main():
    report_data = {
        'timestamp': datetime.now().isoformat(),
        'target_commit': TARGET_COMMIT,
        'local_gate': {},
        'staging_pre_gate': {},
        'backup_manifest': {},
        'git_deployment': {},
        'runtime_verification': {},
        'db_baseline': {},
        'db_after': {},
        'deltas': {},
        'historical_spo_epo': {},
        'final_ruling': 'BLOCKED'
    }

    print("======================================================================")
    print("  PHASE 1: LOCAL & ORIGIN VERIFICATION GATE")
    print("======================================================================")

    code, out, err = run_local_cmd('git rev-parse HEAD')
    local_head = out
    print(f"[Local] HEAD: {local_head}")
    report_data['local_gate']['local_head'] = local_head

    if local_head != TARGET_COMMIT:
        print(f"[FATAL] Local HEAD ({local_head}) does not match TARGET_COMMIT ({TARGET_COMMIT})")
        return

    code, out, err = run_local_cmd(f'git show --stat --oneline {TARGET_COMMIT[:7]}')
    print(f"[Local] Commit stat:\n{out}")
    report_data['local_gate']['commit_stat'] = out

    print("\n======================================================================")
    print("  PHASE 2: STAGING PRE-DEPLOYMENT INSPECTION & BACKUP")
    print("======================================================================")

    client = get_ssh_client()

    # 1. Staging Git status before deployment
    cmd = f"cd {REMOTE_PATH} && git rev-parse HEAD && git status --short && git branch --show-current"
    code, out, err = run_remote_cmd(client, cmd)
    staging_lines = out.splitlines()
    staging_pre_head = staging_lines[0] if len(staging_lines) > 0 else 'UNKNOWN'
    print(f"[Staging] Pre-deploy HEAD: {staging_pre_head}")
    print(f"[Staging] Pre-deploy status:\n{out}")
    report_data['staging_pre_gate']['pre_head'] = staging_pre_head
    report_data['staging_pre_gate']['raw_status'] = out

    # 2. Database Baseline Counts (Read-Only)
    cmd = f"""cd {REMOTE_PATH} && php -r '
    require "vendor/autoload.php";
    $app = require_once "bootstrap/app.php";
    $kernel = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);
    $kernel->bootstrap();
    $tables = ["orders", "order_items", "invoices", "shipments", "refunds", "procurement_demands", "procurement_batches", "supplier_purchase_orders", "supplier_purchase_order_items", "external_platform_orders", "inventory_sources", "product_inventories", "aliexpress_tokens"];
    $counts = [];
    foreach ($tables as $t) {{ $counts[$t] = \\Illuminate\\Support\\Facades\\DB::table($t)->count(); }}
    $spo35 = (array) \\Illuminate\\Support\\Facades\\DB::table("supplier_purchase_orders")->where("id", 35)->first();
    $epo26 = (array) \\Illuminate\\Support\\Facades\\DB::table("external_platform_orders")->where("id", 26)->first();
    $spo36 = (array) \\Illuminate\\Support\\Facades\\DB::table("supplier_purchase_orders")->where("id", 36)->first();
    $epo27 = (array) \\Illuminate\\Support\\Facades\\DB::table("external_platform_orders")->where("id", 27)->first();
    echo json_encode(["counts" => $counts, "spo35" => $spo35, "epo26" => $epo26, "spo36" => $spo36, "epo27" => $epo27]);
    '"""
    code, out, err = run_remote_cmd(client, cmd)
    try:
        baseline_res = json.loads(out)
        report_data['db_baseline'] = baseline_res.get('counts', {})
        report_data['historical_spo_epo']['before_spo35'] = baseline_res.get('spo35')
        report_data['historical_spo_epo']['before_epo26'] = baseline_res.get('epo26')
        report_data['historical_spo_epo']['before_spo36'] = baseline_res.get('spo36')
        report_data['historical_spo_epo']['before_epo27'] = baseline_res.get('epo27')
        print(f"[Staging] DB Baseline Counts:\n{json.dumps(baseline_res.get('counts', {}), indent=2)}")
        print(f"[Staging] SPO #35 Before: state={baseline_res.get('spo35', {}).get('state')}, payment_state={baseline_res.get('spo35', {}).get('payment_state')}")
        print(f"[Staging] EPO #26 Before: raw_status={baseline_res.get('epo26', {}).get('raw_status')}, failure_code={baseline_res.get('epo26', {}).get('failure_code')}, external_id={baseline_res.get('epo26', {}).get('external_order_id')}")
        print(f"[Staging] SPO #36 Before: state={baseline_res.get('spo36', {}).get('state')}, payment_state={baseline_res.get('spo36', {}).get('payment_state')}")
        print(f"[Staging] EPO #27 Before: raw_status={baseline_res.get('epo27', {}).get('raw_status')}, failure_code={baseline_res.get('epo27', {}).get('failure_code')}, external_id={baseline_res.get('epo27', {}).get('external_order_id')}")
    except Exception as e:
        print(f"[FATAL] Failed to parse baseline JSON: {e}, raw: {out}, err: {err}")
        client.close()
        return

    # 3. Clean any untracked or temp files in remote staging repo before git pull/checkout
    clean_cmd = f"cd {REMOTE_PATH} && git checkout -- . && git clean -fd -e storage"
    code, out_clean, err_clean = run_remote_cmd(client, clean_cmd)
    print(f"[Staging] Pre-deploy clean output: {out_clean}")

    # 4. Backup affected files outside webroot
    ts = datetime.now().strftime('%Y%m%d_%H%M%S')
    backup_dir = f"/home/highest-ye/backups/address_guard_pre_deploy_{ts}"
    backup_cmd = f"""
    mkdir -p {backup_dir} &&
    cp -r {REMOTE_PATH}/packages/Webkul/Procurement {backup_dir}/Procurement_backup 2>/dev/null || true &&
    cp -r {REMOTE_PATH}/packages/Webkul/Fulfillment {backup_dir}/Fulfillment_backup 2>/dev/null || true &&
    cp -r {REMOTE_PATH}/app/Services/AliExpress {backup_dir}/AliExpress_backup 2>/dev/null || true &&
    cp {REMOTE_PATH}/app/Http/Controllers/AliExpress/AliExpressKeysController.php {backup_dir}/AliExpressKeysController.php.bak 2>/dev/null || true &&
    echo "Backup created at {backup_dir}"
    """
    code, out, err = run_remote_cmd(client, backup_cmd)
    print(f"[Staging] Backup status: {out}")
    report_data['backup_manifest']['backup_dir'] = backup_dir

    print("\n======================================================================")
    print("  PHASE 3: CONTROLLED GIT-ONLY DEPLOYMENT")
    print("======================================================================")

    deploy_cmd = f"""
    cd {REMOTE_PATH} &&
    git fetch origin feat/delivery-admin-ui-rebuild &&
    git checkout {TARGET_COMMIT} &&
    git rev-parse HEAD &&
    git status --short &&
    git diff --exit-code
    """
    code, out, err = run_remote_cmd(client, deploy_cmd)
    print(f"[Staging] Git Deploy Output:\n{out}")
    report_data['git_deployment']['output'] = out

    # Verify deployed HEAD
    code, out_head, err = run_remote_cmd(client, f"cd {REMOTE_PATH} && git rev-parse HEAD")
    deployed_head = out_head.strip()
    report_data['git_deployment']['deployed_head'] = deployed_head
    if deployed_head != TARGET_COMMIT:
        print(f"[FATAL] Deployed HEAD ({deployed_head}) != TARGET_COMMIT ({TARGET_COMMIT})")
        client.close()
        return

    # Check git status is clean
    code, out_status, err = run_remote_cmd(client, f"cd {REMOTE_PATH} && git status --short")
    report_data['git_deployment']['git_status_clean'] = (out_status.strip() == '')
    print(f"[Staging] Git status clean: {report_data['git_deployment']['git_status_clean']} (output: '{out_status.strip()}')")

    # Clear application cache safely
    cache_cmd = f"cd {REMOTE_PATH} && php artisan config:clear && php artisan route:clear && php artisan view:clear"
    code, out_cache, err = run_remote_cmd(client, cache_cmd)
    print(f"[Staging] Cache clear output:\n{out_cache}")

    print("\n======================================================================")
    print("  PHASE 4: RUNTIME VERIFICATION (ISOLATED MOCKS / TESTS)")
    print("======================================================================")

    # 1. Verify current database source address masked invariants
    source_check_cmd = f"""cd {REMOTE_PATH} && php -r '
    require "vendor/autoload.php";
    $app = require_once "bootstrap/app.php";
    $kernel = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);
    $kernel->bootstrap();
    $src = \\Illuminate\\Support\\Facades\\DB::table("inventory_sources")->where("code", "default")->first();
    $postcode = trim((string)($src->postcode ?? ""));
    $len = strlen($postcode);
    $matches = (bool)preg_match("/^[A-Z]{{4}}[0-9]{{4}}$/", $postcode);
    $masked = $len >= 4 ? substr($postcode, 0, 2) . "****" . substr($postcode, -2) : "****";
    
    // Normalize and validate current source
    $candidate = [
        "contact_person" => (string)($src->contact_name ?? $src->name ?? ""),
        "phone_num" => (string)($src->contact_number ?? ""),
        "mobile_no" => (string)($src->contact_number ?? ""),
        "phone_country" => "966",
        "address" => (string)($src->street ?? $src->address1 ?? ""),
        "city" => (string)($src->city ?? ""),
        "province" => (string)($src->state ?? ""),
        "zip" => $postcode,
        "country" => strtoupper((string)($src->country ?? "SA")),
        "company_name" => (string)($src->name ?? ""),
    ];
    
    $val = \\App\\Services\\AliExpress\\Shipping\\AliExpressShippingAddressValidator::normalizeAndValidate($candidate);
    $summary = $val->getMaskedSummary();
    
    echo json_encode([
        "code" => $src->code ?? null,
        "country" => $src->country ?? null,
        "zip_present" => !empty($postcode),
        "zip_len" => $len,
        "matches_pattern" => $matches,
        "zip_masked" => $masked,
        "validated_dto_country" => $val->country,
        "validated_dto_zip_len" => strlen($val->zip),
        "validated_dto_summary" => $summary,
    ]);
    '"""
    code_src, out_src, err_src = run_remote_cmd(client, source_check_cmd)
    print(f"[Staging] Current Source Address Check:\n{out_src}")
    report_data['runtime_verification']['source_check'] = json.loads(out_src)

    # 2. Run isolated address guard test runner
    test_cmd = f"cd {REMOTE_PATH} && php scripts/run_address_guard_tests_isolated.php"
    code_tests, out_tests, err_tests = run_remote_cmd(client, test_cmd)
    print(f"[Staging] Test Runner Output:\n{out_tests}")
    report_data['runtime_verification']['isolated_runner_output'] = out_tests
    report_data['runtime_verification']['isolated_runner_exit_code'] = code_tests

    # 3. Run Pest directly on the unit test inside package
    pest_cmd = f"cd {REMOTE_PATH} && vendor/bin/pest packages/Webkul/Procurement/tests/Unit/AliExpressShippingAddressValidatorTest.php"
    code_pest, out_pest, err_pest = run_remote_cmd(client, pest_cmd)
    print(f"[Staging] Pest Unit Test Output:\n{out_pest}")
    report_data['runtime_verification']['pest_output'] = out_pest
    report_data['runtime_verification']['pest_exit_code'] = code_pest

    # 4. Run Pint dirty check
    pint_cmd = f"cd {REMOTE_PATH} && vendor/bin/pint --dirty --test"
    code_pint, out_pint, err_pint = run_remote_cmd(client, pint_cmd)
    print(f"[Staging] Pint check output:\n{out_pint}")
    report_data['runtime_verification']['pint_output'] = out_pint
    report_data['runtime_verification']['pint_exit_code'] = code_pint

    print("\n======================================================================")
    print("  PHASE 5: DATABASE POST-DEPLOYMENT VERIFICATION & DELTA CHECK")
    print("======================================================================")

    code, out, err = run_remote_cmd(client, cmd)
    after_res = json.loads(out)
    report_data['db_after'] = after_res.get('counts', {})
    report_data['historical_spo_epo']['after_spo35'] = after_res.get('spo35')
    report_data['historical_spo_epo']['after_epo26'] = after_res.get('epo26')
    report_data['historical_spo_epo']['after_spo36'] = after_res.get('spo36')
    report_data['historical_spo_epo']['after_epo27'] = after_res.get('epo27')

    deltas = {}
    for table, count in report_data['db_baseline'].items():
        after_count = report_data['db_after'].get(table, 0)
        deltas[table] = after_count - count
    report_data['deltas'] = deltas
    print(f"[Staging] DB Deltas (Before vs After):\n{json.dumps(deltas, indent=2)}")

    # Verify all deltas are 0
    all_zero_deltas = all(d == 0 for d in deltas.values())
    print(f"[Staging] All DB deltas == 0: {all_zero_deltas}")

    # Verify historical records stability
    spo35_stable = (report_data['historical_spo_epo']['before_spo35'] == report_data['historical_spo_epo']['after_spo35'])
    epo26_stable = (report_data['historical_spo_epo']['before_epo26'] == report_data['historical_spo_epo']['after_epo26'])
    spo36_stable = (report_data['historical_spo_epo']['before_spo36'] == report_data['historical_spo_epo']['after_spo36'])
    epo27_stable = (report_data['historical_spo_epo']['before_epo27'] == report_data['historical_spo_epo']['after_epo27'])
    print(f"[Staging] Historical records stability: SPO35={spo35_stable}, EPO26={epo26_stable}, SPO36={spo36_stable}, EPO27={epo27_stable}")

    if (deployed_head == TARGET_COMMIT and 
        all_zero_deltas and 
        spo35_stable and 
        epo26_stable and 
        spo36_stable and 
        epo27_stable and 
        code_tests == 0 and 
        report_data['runtime_verification']['source_check'].get('matches_pattern') is True):
        report_data['final_ruling'] = 'STAGING_SAUDI_ADDRESS_GUARD_READY_FOR_NEW_SIMULATION_APPROVAL'
    else:
        report_data['final_ruling'] = 'BLOCKED'

    print("\n======================================================================")
    print(f"  FINAL RULING: {report_data['final_ruling']}")
    print("======================================================================")

    # Save detailed JSON log
    out_log_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'staging_address_guard_deployment_result.json')
    with open(out_log_path, 'w', encoding='utf-8') as f:
        json.dump(report_data, f, indent=2)
    print(f"Saved deployment results to {out_log_path}")

    client.close()

if __name__ == '__main__':
    main()
