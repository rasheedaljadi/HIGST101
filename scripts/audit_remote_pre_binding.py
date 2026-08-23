import paramiko
import json
import re

hostname = '76.13.79.242'
username = 'highest-ye'
password = 'YoK2PBV1fo82yujX2tDq'
remote_path = '/home/highest-ye/htdocs/highest-ye.store'

def run_remote_commands():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(hostname, username=username, password=password)

    commands = {
        'hostname': 'hostname',
        'app_about': f'cd {remote_path} && php artisan about',
        'git_branch': f'cd {remote_path} && git branch --show-current',
        'git_head': f'cd {remote_path} && git rev-parse HEAD',
        'git_status': f'cd {remote_path} && git status --short',
        'git_log': f'cd {remote_path} && git log --oneline --decorate -n 12',
        'commit_ancestor_1': f'cd {remote_path} && git merge-base --is-ancestor 4963825d198bb9cf8f1088c42661f4fa9b47e5b2 HEAD; echo $?',
        'commit_ancestor_2': f'cd {remote_path} && git merge-base --is-ancestor 32b3245f04026fa8a67c790f24d4bd03a304832b HEAD; echo $?',
        'migrate_status': f'cd {remote_path} && php artisan migrate:status',
        'schema_tinker': f"""cd {remote_path} && php artisan tinker --execute="echo json_encode([
            'order_lifecycle_stage_views' => Schema::hasTable('order_lifecycle_stage_views'),
            'order_item_lifecycle_stage_views' => Schema::hasTable('order_item_lifecycle_stage_views'),
            'order_view_columns' => Schema::hasTable('order_lifecycle_stage_views') ? Schema::getColumnListing('order_lifecycle_stage_views') : [],
            'item_view_columns' => Schema::hasTable('order_item_lifecycle_stage_views') ? Schema::getColumnListing('order_item_lifecycle_stage_views') : [],
        ], JSON_PRETTY_PRINT);" """,
        'data_counts_tinker': f"""cd {remote_path} && php artisan tinker --execute="echo json_encode([
            'orders_count' => DB::table('orders')->count(),
            'order_items_count' => DB::table('order_items')->count(),
            'read_model_orders_count' => Schema::hasTable('order_lifecycle_stage_views') ? DB::table('order_lifecycle_stage_views')->count() : 0,
            'read_model_items_count' => Schema::hasTable('order_item_lifecycle_stage_views') ? DB::table('order_item_lifecycle_stage_views')->count() : 0,
            'stage_distribution' => Schema::hasTable('order_lifecycle_stage_views') ? DB::table('order_lifecycle_stage_views')->select('bottleneck_stage_code', DB::raw('count(*) as count'))->groupBy('bottleneck_stage_code')->get() : [],
            'exceptions_count' => Schema::hasTable('order_lifecycle_stage_views') ? DB::table('order_lifecycle_stage_views')->where('is_exception', true)->count() : 0,
            'exception_reasons' => Schema::hasTable('order_lifecycle_stage_views') ? DB::table('order_lifecycle_stage_views')->where('is_exception', true)->select('exception_reason', DB::raw('count(*) as count'))->groupBy('exception_reason')->get() : [],
            'inventory_sources' => DB::table('inventory_sources')->select('id', 'code', 'name')->get(),
            'default_qty' => DB::table('product_inventories')->join('inventory_sources', 'product_inventories.inventory_source_id', '=', 'inventory_sources.id')->where('inventory_sources.code', 'default')->sum('qty'),
            'ae_source_qty' => DB::table('product_inventories')->join('inventory_sources', 'product_inventories.inventory_source_id', '=', 'inventory_sources.id')->where('inventory_sources.code', 'aliexpress_source')->sum('qty'),
        ], JSON_PRETTY_PRINT);" """,
        'code_registration_check': f"""cd {remote_path} && php artisan tinker --execute="echo json_encode([
            'OrderLifecycleEventSubscriber' => class_exists('Webkul\\\\Sales\\\\Listeners\\\\OrderLifecycleEventSubscriber'),
            'OrderLifecycleStageResolver' => class_exists('Webkul\\\\Sales\\\\Services\\\\Lifecycle\\\\OrderLifecycleStageResolver'),
            'OrderLifecycleProjector' => class_exists('Webkul\\\\Sales\\\\Services\\\\Lifecycle\\\\OrderLifecycleProjector'),
            'OrderLifecycleRebuildService' => class_exists('Webkul\\\\Sales\\\\Services\\\\Lifecycle\\\\OrderLifecycleRebuildService'),
            'OrderLifecycleStageView' => class_exists('Webkul\\\\Sales\\\\Models\\\\OrderLifecycleStageView'),
        ], JSON_PRETTY_PRINT);" """
    }

    results = {}
    for key, cmd in commands.items():
        stdin, stdout, stderr = ssh.exec_command(cmd)
        out = stdout.read().decode('utf-8')
        err = stderr.read().decode('utf-8')
        results[key] = out.strip() or err.strip()

    ssh.close()
    return results

if __name__ == '__main__':
    res = run_remote_commands()
    print("=== REMOTE AUDIT RESULTS ===")
    for k, v in res.items():
        print(f"--- {k} ---")
        print(v)
