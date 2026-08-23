import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    commands = {
        'user_cat_service': "systemctl --user cat highest-queue-aliexpress-webhooks.service 2>&1",
        'linger_status': "loginctl show-user highest-ye -p Linger 2>&1",
        'sudo_check': "sudo -n -l 2>&1",
        'can_enable_linger': "loginctl enable-linger highest-ye 2>&1",
        'sudo_systemd_check': "sudo -n systemctl status 2>&1 | head -n 5",
        'crontab_list': "crontab -l 2>&1",
        'running_workers': "ps aux | grep -E 'queue:work|artisan' | grep -v grep",
        'php_binary': "which php php8.4 php8.3 2>&1",
        'audit_log_record_metadata': f"cd {remote_base} && php -r \"require 'vendor/autoload.php'; \$app=require 'bootstrap/app.php'; \$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); \$rec = Webkul\\Procurement\\Models\\ProcurementAuditLog::where('action', 'aliexpress_oauth_expiration_warning')->first(); echo json_encode(\$rec ? \$rec->only(['id', 'auditable_type', 'auditable_id', 'action', 'actor_type', 'correlation_id', 'created_at']) : null, JSON_PRETTY_PRINT);\"",
        'domain_counts': f"cd {remote_base} && php -r \"require 'vendor/autoload.php'; \$app=require 'bootstrap/app.php'; \$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); use Illuminate\\Support\\Facades\\DB; use Illuminate\\Support\\Facades\\Schema; \$tables = ['external_platform_orders', 'supplier_purchase_orders', 'procurement_batches', 'procurement_demands', 'procurement_demand_allocations', 'procurement_cost_snapshots', 'procurement_audit_logs', 'aliexpress_webhook_inbox_messages']; \$counts = []; foreach (\$tables as \$t) {{ \$counts[\$t] = Schema::hasTable(\$t) ? DB::table(\$t)->count() : null; }} echo json_encode(\$counts, JSON_PRETTY_PRINT);\"",
    }
    
    results = {}
    for key, cmd in commands.items():
        code, out, err = run_remote_cmd(client, cmd)
        results[key] = out.strip()
        
    print(json.dumps(results, indent=2))
    client.close()

if __name__ == '__main__':
    main()
