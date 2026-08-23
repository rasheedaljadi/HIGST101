import json
import sys
from remote_ssh_helper import get_ssh_client, run_remote_cmd

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

APP_PATH = '/home/highest-ye/htdocs/highest-ye.store'
TARGET_COMMIT = '0316298afa2c15ae5aca6b312d4b7b5f284a01e0'
BASELINE_COMMIT = '02658011a0a9f55e4b75b520b0d967dab7ade336'
V2_FOUNDATION = 'c3501525c500858ee7493ea19904beb97bfd6a94'
SAFEGUARDS_COMMIT = '4c3b867dc6374eff7b62bdb6535ed7af823504d5'

def main():
    client = get_ssh_client()
    
    print("\n=== PHASE 0: Staging Environment Baseline Audit (Read-Only) ===")
    
    # 1. Host identity
    _, hostname, _ = run_remote_cmd(client, "hostname")
    _, uname, _ = run_remote_cmd(client, "uname -a")
    _, whoami, _ = run_remote_cmd(client, "whoami")
    _, php_v, _ = run_remote_cmd(client, "php -v | head -n 1")
    
    print(f"Host: {hostname} ({whoami})")
    print(f"OS: {uname}")
    print(f"PHP: {php_v}")
    print(f"App Path: {APP_PATH}")
    
    # 2. Git Status before changes
    _, git_head, _ = run_remote_cmd(client, f"cd {APP_PATH} && git rev-parse HEAD")
    _, git_branch, _ = run_remote_cmd(client, f"cd {APP_PATH} && git branch --show-current")
    _, git_status_short, _ = run_remote_cmd(client, f"cd {APP_PATH} && git status --short")
    _, git_remotes, _ = run_remote_cmd(client, f"cd {APP_PATH} && git remote -v")
    
    print(f"\nCurrent Git HEAD: {git_head}")
    print(f"Current Git Branch: {git_branch}")
    print(f"Git Status Short:\n{git_status_short if git_status_short else '(clean)'}")
    print(f"Git Remotes:\n{git_remotes}")
    
    is_clean = (len(git_status_short.strip()) == 0)
    print(f"Repository Clean: {is_clean}")
    
    # 3. Git Fetch to synchronize remote tracking refs
    print("\nRunning 'git fetch --all --tags --prune'...")
    code_fetch, fetch_out, fetch_err = run_remote_cmd(client, f"cd {APP_PATH} && git fetch --all --tags --prune")
    print(f"Fetch result: code={code_fetch}")
    if fetch_out: print(fetch_out)
    if fetch_err: print(fetch_err)
    
    # 4. Check if target commit exists and verify lineage on remote
    code_cat, target_type, _ = run_remote_cmd(client, f"cd {APP_PATH} && git cat-file -t {TARGET_COMMIT}")
    target_present = (target_type == 'commit')
    print(f"Target commit ({TARGET_COMMIT}) present on remote: {target_present}")
    
    lineage_valid = False
    if target_present:
        cmd_lineage = (
            f"cd {APP_PATH} && "
            f"git merge-base --is-ancestor {BASELINE_COMMIT} {V2_FOUNDATION} && "
            f"git merge-base --is-ancestor {V2_FOUNDATION} {SAFEGUARDS_COMMIT} && "
            f"git merge-base --is-ancestor {SAFEGUARDS_COMMIT} {TARGET_COMMIT} && "
            f"echo 'VALID'"
        )
        _, lineage_res, _ = run_remote_cmd(client, cmd_lineage)
        lineage_valid = (lineage_res.strip() == 'VALID')
        print(f"Lineage verification ({BASELINE_COMMIT} -> {V2_FOUNDATION} -> {SAFEGUARDS_COMMIT} -> {TARGET_COMMIT}): {lineage_valid}")
    else:
        print("Target commit not yet in remote git object database. Will need push from local or branch sync.")
    
    # 5. Read-only application status
    _, about_out, _ = run_remote_cmd(client, f"cd {APP_PATH} && php artisan about")
    _, migrate_status, _ = run_remote_cmd(client, f"cd {APP_PATH} && php artisan migrate:status")
    _, routes_proc, _ = run_remote_cmd(client, f"cd {APP_PATH} && php artisan route:list --name=procurement")
    
    # 6. Read-only runtime config
    php_flags_cmd = (
        f"cd {APP_PATH} && php -r \""
        "require 'vendor/autoload.php'; "
        "$app = require_once 'bootstrap/app.php'; "
        "$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class); "
        "$kernel->bootstrap(); "
        "echo json_encode([ "
        "    'v2_enabled' => config('procurement.v2_enabled', false), "
        "    'polling_enabled' => config('procurement.polling.enabled', true), "
        "    'app_env' => config('app.env'), "
        "    'app_debug' => config('app.debug'), "
        "    'db_database' => config('database.connections.mysql.database'), "
        "]);\""
    )
    _, flags_json, _ = run_remote_cmd(client, php_flags_cmd)
    print(f"\nRuntime Config: {flags_json}")
    
    # 7. Database table and record counts (read-only)
    php_db_cmd = (
        f"cd {APP_PATH} && php -r \""
        "require 'vendor/autoload.php'; "
        "$app = require_once 'bootstrap/app.php'; "
        "$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class); "
        "$kernel->bootstrap(); "
        "$tables = Illuminate\\Support\\Facades\\DB::select('SHOW TABLES'); "
        "$tableList = array_map(function($t) { return array_values((array)$t)[0]; }, $tables); "
        "$hasV1Po = in_array('purchase_orders', $tableList); "
        "$v1Count = $hasV1Po ? Illuminate\\Support\\Facades\\DB::table('purchase_orders')->count() : 0; "
        "$ordersCount = in_array('orders', $tableList) ? Illuminate\\Support\\Facades\\DB::table('orders')->count() : 0; "
        "$procTables = array_values(array_filter($tableList, function($t) { return str_starts_with($t, 'procurement_') || str_starts_with($t, 'supplier_purchase_'); })); "
        "echo json_encode([ "
        "    'total_tables' => count($tableList), "
        "    'existing_procurement_tables' => $procTables, "
        "    'orders_count' => $ordersCount, "
        "    'v1_purchase_orders_count' => $v1Count, "
        "]);\""
    )
    _, db_counts_json, _ = run_remote_cmd(client, php_db_cmd)
    print(f"\nDatabase Snapshot: {db_counts_json}")
    
    # Save results to file
    phase0_data = {
        "hostname": hostname,
        "uname": uname,
        "user": whoami,
        "php_version": php_v,
        "app_path": APP_PATH,
        "git_head": git_head,
        "git_branch": git_branch,
        "git_status_clean": is_clean,
        "git_status_short": git_status_short,
        "target_commit_present": target_present,
        "lineage_valid": lineage_valid,
        "runtime_config": json.loads(flags_json) if flags_json.startswith('{') else flags_json,
        "db_snapshot": json.loads(db_counts_json) if db_counts_json.startswith('{') else db_counts_json,
        "migrate_status_lines": len(migrate_status.splitlines()),
        "procurement_routes_count": len(routes_proc.splitlines()) if routes_proc else 0,
    }
    
    with open('scripts/phase0_audit_result.json', 'w', encoding='utf-8') as f:
        json.dump(phase0_data, f, indent=2, ensure_ascii=False)
        
    print("\nPhase 0 Audit successfully completed.")
    
    # Gate 0 evaluation
    if not is_clean:
        print("STOP GATE 0 FAILED: Git working tree on remote is not clean!")
        sys.exit(1)
        
    client.close()

if __name__ == '__main__':
    main()
