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

from remote_ssh_helper import get_ssh_client, run_remote_cmd

TARGET_COMMIT = 'fffd0d1c42cefd9b10dc63e307c083dd9f83ef40'
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

    code, out, err = run_local_cmd('git show --stat --oneline fffd0d1')
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
    echo json_encode(["counts" => $counts, "spo35" => $spo35, "epo26" => $epo26]);
    '"""
    code, out, err = run_remote_cmd(client, cmd)
    try:
        baseline_res = json.loads(out)
        report_data['db_baseline'] = baseline_res.get('counts', {})
        report_data['historical_spo_epo']['before_spo35'] = baseline_res.get('spo35')
        report_data['historical_spo_epo']['before_epo26'] = baseline_res.get('epo26')
        print(f"[Staging] DB Baseline Counts: {json.dumps(baseline_res.get('counts', {}), indent=2)}")
        print(f"[Staging] SPO #35 Before: state={baseline_res.get('spo35', {}).get('state')}, payment_state={baseline_res.get('spo35', {}).get('payment_state')}")
        print(f"[Staging] EPO #26 Before: raw_status={baseline_res.get('epo26', {}).get('raw_status')}, failure_code={baseline_res.get('epo26', {}).get('failure_code')}, external_id={baseline_res.get('epo26', {}).get('external_order_id')}")
    except Exception as e:
        print(f"[FATAL] Failed to parse baseline JSON: {e}, raw: {out}, err: {err}")
        client.close()
        return

    # 3. Backup affected files outside webroot
    ts = datetime.now().strftime('%Y%m%d_%H%M%S')
    backup_dir = f"/home/highest-ye/backups/auth_context_pre_deploy_{ts}"
    backup_cmd = f"""
    mkdir -p {backup_dir} &&
    cp -r {REMOTE_PATH}/packages/Webkul/Procurement {backup_dir}/Procurement_backup 2>/dev/null || true
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

    # Clear application cache safely
    cache_cmd = f"cd {REMOTE_PATH} && php artisan config:clear && php artisan route:clear && php artisan view:clear"
    code, out_cache, err = run_remote_cmd(client, cache_cmd)
    print(f"[Staging] Cache clear output:\n{out_cache}")

    print("\n======================================================================")
    print("  PHASE 4: RUNTIME VERIFICATION (ISOLATED MOCKS / TESTS)")
    print("======================================================================")

    # Run unit tests on Staging via pest / php
    test_cmd = f"cd {REMOTE_PATH} && php scripts/run_auth_context_remediation_tests.php"
    code, out_tests, err_tests = run_remote_cmd(client, test_cmd)
    print(f"[Staging] Test Runner Output:\n{out_tests}")
    report_data['runtime_verification']['test_runner_output'] = out_tests
    report_data['runtime_verification']['test_exit_code'] = code

    # Run Pest directly on the unit test inside package
    pest_cmd = f"cd {REMOTE_PATH} && vendor/bin/pest packages/Webkul/Procurement/tests/Unit/AliExpressAuthorizationResolverTest.php"
    code_pest, out_pest, err_pest = run_remote_cmd(client, pest_cmd)
    print(f"[Staging] Pest Unit Test Output:\n{out_pest}")
    report_data['runtime_verification']['pest_output'] = out_pest
    report_data['runtime_verification']['pest_exit_code'] = code_pest

    # Run Pint dirty check
    pint_cmd = f"cd {REMOTE_PATH} && vendor/bin/pint --dirty --test"
    code_pint, out_pint, err_pint = run_remote_cmd(client, pint_cmd)
    print(f"[Staging] Pint check output:\n{out_pint}")
    report_data['runtime_verification']['pint_output'] = out_pint

    print("\n======================================================================")
    print("  PHASE 5: POST-DEPLOYMENT DATABASE & INVARIANT VERIFICATION")
    print("======================================================================")

    code, out_after, err = run_remote_cmd(client, cmd)
    try:
        after_res = json.loads(out_after)
        report_data['db_after'] = after_res.get('counts', {})
        report_data['historical_spo_epo']['after_spo35'] = after_res.get('spo35')
        report_data['historical_spo_epo']['after_epo26'] = after_res.get('epo26')
        
        # Calculate deltas
        deltas = {}
        all_zero = True
        for t, cnt in report_data['db_baseline'].items():
            after_cnt = report_data['db_after'].get(t, 0)
            delta = after_cnt - cnt
            deltas[t] = delta
            if delta != 0:
                all_zero = False
        report_data['deltas'] = deltas
        print(f"[Staging] DB Deltas: {json.dumps(deltas, indent=2)}")

        # Verify historical records unchanged
        spo35_unchanged = (
            report_data['historical_spo_epo']['after_spo35'].get('state') == 'supplier_exception' and
            report_data['historical_spo_epo']['after_spo35'].get('payment_state') == 'submission_failed'
        )
        epo26_unchanged = (
            report_data['historical_spo_epo']['after_epo26'].get('raw_status') == 'SUBMISSION_FAILED' and
            report_data['historical_spo_epo']['after_epo26'].get('failure_code') == 'IllegalAccessToken' and
            report_data['historical_spo_epo']['after_epo26'].get('external_order_id') is None
        )

        print(f"[Staging] SPO #35 Unchanged: {spo35_unchanged}")
        print(f"[Staging] EPO #26 Unchanged: {epo26_unchanged}")
        print(f"[Staging] All Table Deltas Zero: {all_zero}")

        if all_zero and spo35_unchanged and epo26_unchanged and code == 0 and deployed_head == TARGET_COMMIT:
            report_data['final_ruling'] = 'STAGING_PROVIDER_CONTEXT_READY_FOR_NEW_SIMULATION_APPROVAL'
        else:
            report_data['final_ruling'] = 'STAGING_PROVIDER_CONTEXT_DEPLOYMENT_BLOCKED — Invariant check failed'

    except Exception as e:
        print(f"[FATAL] Failed to parse after JSON: {e}, raw: {out_after}")

    client.close()

    print("\n======================================================================")
    print(f"  FINAL RULING: {report_data['final_ruling']}")
    print("======================================================================")

    # Save execution log JSON
    with open('scripts/controlled_staging_deployment_result.json', 'w', encoding='utf-8') as f:
        json.dump(report_data, f, indent=2, ensure_ascii=False)
    print("Result saved to scripts/controlled_staging_deployment_result.json")

if __name__ == '__main__':
    main()
