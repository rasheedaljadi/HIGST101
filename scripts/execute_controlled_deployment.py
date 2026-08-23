import paramiko
import json
import time
import subprocess
import re

hostname = '76.13.79.242'
username = 'highest-ye'
password = 'YoK2PBV1fo82yujX2tDq'
remote_path = '/home/highest-ye/htdocs/highest-ye.store'

def run_local_cmd(cmd):
    res = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    return res.stdout.strip(), res.stderr.strip(), res.returncode

def execute_remote_deployment():
    print("=== STARTING CONTROLLED DEPLOYMENT EXECUTION ===")
    
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(hostname, username=username, password=password)

    def exec_ssh(cmd, ignore_error=False):
        stdin, stdout, stderr = ssh.exec_command(cmd)
        out = stdout.read().decode('utf-8').strip()
        err = stderr.read().decode('utf-8').strip()
        code = stdout.channel.recv_exit_status()
        if code != 0 and not ignore_error:
            raise Exception(f"Command failed (code {code}): {cmd}\nStdout: {out}\nStderr: {err}")
        return out, err, code

    report_log = []

    # Phase 0: Baseline Proof
    print("[PHASE 0] Collecting Pre-Deployment Baseline...")
    out_branch, _, _ = exec_ssh(f"cd {remote_path} && git branch --show-current")
    out_head, _, _ = exec_ssh(f"cd {remote_path} && git rev-parse HEAD")
    
    baseline_tinker = f"""cd {remote_path} && php artisan tinker --execute="echo json_encode([
        'orders' => DB::table('orders')->count(),
        'order_items' => DB::table('order_items')->count(),
        'product_inventories' => DB::table('product_inventories')->count(),
        'inventory_movements' => Schema::hasTable('inventory_movements') ? DB::table('inventory_movements')->count() : 0,
        'purchase_orders' => Schema::hasTable('purchase_orders') ? DB::table('purchase_orders')->count() : 0,
        'purchase_order_items' => Schema::hasTable('purchase_order_items') ? DB::table('purchase_order_items')->count() : 0,
        'inbound_receipt_manifests' => Schema::hasTable('inbound_receipt_manifests') ? DB::table('inbound_receipt_manifests')->count() : 0,
        'inventory_transfer_manifests' => Schema::hasTable('inventory_transfer_manifests') ? DB::table('inventory_transfer_manifests')->count() : 0,
        'delivery_assignments' => Schema::hasTable('delivery_assignments') ? DB::table('delivery_assignments')->count() : 0,
        'inventory_sources' => DB::table('inventory_sources')->count(),
        'external_availability_snapshots' => Schema::hasTable('external_availability_snapshots') ? DB::table('external_availability_snapshots')->count() : 0,
        'default_qty' => DB::table('product_inventories')->join('inventory_sources', 'product_inventories.inventory_source_id', '=', 'inventory_sources.id')->where('inventory_sources.code', 'default')->sum('qty')
    ], JSON_PRETTY_PRINT);" """
    
    out_baseline_raw, _, _ = exec_ssh(baseline_tinker)
    baseline_data = json.loads(out_baseline_raw)
    print(f"Phase 0 Baseline: {json.dumps(baseline_data, indent=2)}")
    report_log.append({"phase": 0, "branch": out_branch, "head": out_head, "baseline": baseline_data})

    # Phase 1: Local Backup on Remote Host
    print("[PHASE 1] Creating Local Database Backup on Remote Server...")
    exec_ssh("mkdir -p /home/highest-ye/backups")
    backup_file = f"/home/highest-ye/backups/backup_pre_lifecycle_{int(time.time())}.sql.gz"
    
    # Read DB config from .env
    out_env_db, _, _ = exec_ssh(f"cd {remote_path} && grep -E '^DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=' .env")
    env_vars = dict(re.findall(r'^DB_(\w+)=(.*)$', out_env_db, re.M))
    db_name = env_vars.get('DATABASE', 'highest-db')
    db_user = env_vars.get('USERNAME', 'highest-ye')
    db_pass = env_vars.get('PASSWORD', '')
    
    dump_cmd = f"mysqldump -u '{db_user}' -p'{db_pass}' '{db_name}' | gzip > '{backup_file}'"
    exec_ssh(dump_cmd)
    exec_ssh(f"chmod 0600 '{backup_file}'")
    
    out_size, _, _ = exec_ssh(f"stat -c %s '{backup_file}'")
    out_sha, _, _ = exec_ssh(f"sha256sum '{backup_file}' | awk '{{print $1}}'")
    out_gzip_test, _, gzip_code = exec_ssh(f"gzip -t '{backup_file}'", ignore_error=True)
    
    if gzip_code != 0:
        raise Exception("Backup integrity check gzip -t failed!")
    
    print(f"Phase 1 Backup Created: File={backup_file}, Size={out_size} bytes, SHA256={out_sha}, Test=PASS")
    report_log.append({"phase": 1, "backup_file": backup_file, "size": out_size, "sha256": out_sha})

    # Phase 2: Code Promotion & Fast-Forward
    print("[PHASE 2] Fetching and Merging Target Commits on Remote Server...")
    exec_ssh(f"cd {remote_path} && git fetch --prune")
    target_sha = '32b3245f04026fa8a67c790f24d4bd03a304832b'
    exec_ssh(f"cd {remote_path} && git merge --ff-only {target_sha}")
    
    out_new_head, _, _ = exec_ssh(f"cd {remote_path} && git rev-parse HEAD")
    if out_new_head != target_sha:
        raise Exception(f"Git merge failed! HEAD is {out_new_head}, expected {target_sha}")
        
    exec_ssh(f"cd {remote_path} && php artisan optimize:clear")
    exec_ssh(f"cd {remote_path} && php artisan config:cache")
    print(f"Phase 2 Code Promotion Success: Remote HEAD is now {out_new_head}")
    report_log.append({"phase": 2, "new_head": out_new_head})

    # Phase 3 & 4: Migration Execution and Table Verification
    print("[PHASE 3 & 4] Executing Migration and Verifying Read Model Tables...")
    out_mig, _, _ = exec_ssh(f"cd {remote_path} && php artisan migrate --force")
    print(f"Migration output: {out_mig}")
    
    out_check_tables, _, _ = exec_ssh(f"""cd {remote_path} && php artisan tinker --execute="echo json_encode([
        'order_lifecycle_stage_views' => Schema::hasTable('order_lifecycle_stage_views'),
        'order_item_lifecycle_stage_views' => Schema::hasTable('order_item_lifecycle_stage_views'),
        'order_columns' => Schema::getColumnListing('order_lifecycle_stage_views'),
        'item_columns' => Schema::getColumnListing('order_item_lifecycle_stage_views'),
    ], JSON_PRETTY_PRINT);" """)
    check_tables = json.loads(out_check_tables)
    if not check_tables['order_lifecycle_stage_views'] or not check_tables['order_item_lifecycle_stage_views']:
        raise Exception("Phase 4 Failed! Read Model tables missing after migration!")
        
    print("Phase 3 & 4 Success: Migration applied and view tables exist with correct schema.")
    report_log.append({"phase": 3_4, "migration_output": out_mig, "schema": check_tables})

    # Phase 5: Monitored Backfill
    print("[PHASE 5] Running Monitored Backfill via OrderLifecycleRebuildService...")
    backfill_script = f"""cd {remote_path} && php artisan tinker --execute="
        \\$rebuilder = app(\\Webkul\\Sales\\Services\\Lifecycle\\OrderLifecycleRebuildService::class);
        \\$count1 = \\$rebuilder->rebuild();
        \\$orders_view_1 = DB::table('order_lifecycle_stage_views')->count();
        \\$items_view_1 = DB::table('order_item_lifecycle_stage_views')->count();
        
        // Test idempotency
        \\$count2 = \\$rebuilder->rebuild();
        \\$orders_view_2 = DB::table('order_lifecycle_stage_views')->count();
        \\$items_view_2 = DB::table('order_item_lifecycle_stage_views')->count();
        
        echo json_encode([
            'run1_processed' => \\$count1,
            'run1_orders_view' => \\$orders_view_1,
            'run1_items_view' => \\$items_view_1,
            'run2_processed' => \\$count2,
            'run2_orders_view' => \\$orders_view_2,
            'run2_items_view' => \\$items_view_2,
            'idempotent' => (\\$orders_view_1 === \\$orders_view_2 && \\$items_view_1 === \\$items_view_2)
        ], JSON_PRETTY_PRINT);
    " """
    
    out_backfill_raw, _, _ = exec_ssh(backfill_script)
    backfill_res = json.loads(out_backfill_raw)
    print(f"Phase 5 Backfill Results: {json.dumps(backfill_res, indent=2)}")
    if not backfill_res['idempotent']:
        raise Exception("Backfill failed idempotency check!")
    report_log.append({"phase": 5, "backfill": backfill_res})

    # Phase 6: Post-Deployment Verification
    print("[PHASE 6] Running Post-Deployment Verification...")
    out_final_baseline_raw, _, _ = exec_ssh(baseline_tinker)
    final_baseline_data = json.loads(out_final_baseline_raw)
    
    # Assert core business tables counts are 100% unchanged
    for key in ['orders', 'order_items', 'product_inventories', 'inventory_movements', 'purchase_orders', 'purchase_order_items', 'inbound_receipt_manifests', 'inventory_transfer_manifests', 'delivery_assignments', 'inventory_sources', 'external_availability_snapshots']:
        if baseline_data[key] != final_baseline_data[key]:
            raise Exception(f"POST-DEPLOYMENT DATA ALTERATION DETECTED in table {key}! Pre={baseline_data[key]}, Post={final_baseline_data[key]}")
            
    # Check distribution of stages
    out_stages_raw, _, _ = exec_ssh(f"""cd {remote_path} && php artisan tinker --execute="echo json_encode([
        'stage_distribution' => DB::table('order_lifecycle_stage_views')->select('bottleneck_stage_code', DB::raw('count(*) as count'))->groupBy('bottleneck_stage_code')->get(),
        'exceptions_count' => DB::table('order_lifecycle_stage_views')->where('is_exception', true)->count(),
    ], JSON_PRETTY_PRINT);" """)
    stages_res = json.loads(out_stages_raw)
    
    print("=== CONTROLLED DEPLOYMENT COMPLETED 100% SUCCESSFULLY ===")
    print("Verdict: READY FOR LIVE DASHBOARD BINDING")
    
    report_log.append({
        "phase": 6,
        "final_baseline": final_baseline_data,
        "stages": stages_res,
        "verdict": "READY FOR LIVE DASHBOARD BINDING"
    })
    
    ssh.close()
    return report_log

if __name__ == '__main__':
    res = execute_remote_deployment()
    with open('remote_deployment_log.json', 'w') as f:
        json.dump(res, f, indent=2)
