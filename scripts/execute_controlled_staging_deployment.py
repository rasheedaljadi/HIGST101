import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    files_to_sync = [
        ('app/Services/AliExpress/AliExpressWebhookSignatureVerifier.php', f'{remote_base}/app/Services/AliExpress/AliExpressWebhookSignatureVerifier.php'),
        ('app/Http/Controllers/AliExpress/AliExpressWebhookController.php', f'{remote_base}/app/Http/Controllers/AliExpress/AliExpressWebhookController.php'),
        ('packages/Webkul/Procurement/src/Database/Migrations/2026_08_22_000002_create_aliexpress_webhook_inbox_messages_table.php', f'{remote_base}/packages/Webkul/Procurement/src/Database/Migrations/2026_08_22_000002_create_aliexpress_webhook_inbox_messages_table.php'),
        ('packages/Webkul/Procurement/src/Models/AliExpressWebhookInboxMessage.php', f'{remote_base}/packages/Webkul/Procurement/src/Models/AliExpressWebhookInboxMessage.php'),
        ('packages/Webkul/Procurement/src/Jobs/ProcessAliExpressWebhookJob.php', f'{remote_base}/packages/Webkul/Procurement/src/Jobs/ProcessAliExpressWebhookJob.php'),
        ('packages/Webkul/Procurement/tests/Feature/ProcurementAliExpressWebhookSecureConsumptionTest.php', f'{remote_base}/packages/Webkul/Procurement/tests/Feature/ProcurementAliExpressWebhookSecureConsumptionTest.php'),
    ]
    
    sftp = client.open_sftp()
    
    # Ensure remote dirs exist
    run_remote_cmd(client, f"mkdir -p {remote_base}/app/Services/AliExpress {remote_base}/packages/Webkul/Procurement/src/Database/Migrations {remote_base}/packages/Webkul/Procurement/src/Models {remote_base}/packages/Webkul/Procurement/src/Jobs {remote_base}/packages/Webkul/Procurement/tests/Feature")
    
    uploaded = []
    for local_rel, remote_abs in files_to_sync:
        local_path = os.path.join(base_dir, local_rel)
        with open(local_path, 'r', encoding='utf-8') as f:
            content = f.read()
        with sftp.file(remote_abs, 'w') as rf:
            rf.write(content)
        uploaded.append(local_rel)
        print(f"Uploaded: {local_rel} -> {remote_abs}")
        
    sftp.close()
    
    # Run specific allowlisted migration only
    migration_rel_path = "packages/Webkul/Procurement/src/Database/Migrations/2026_08_22_000002_create_aliexpress_webhook_inbox_messages_table.php"
    migrate_cmd = f"cd {remote_base} && php artisan migrate --path={migration_rel_path}"
    code, migrate_out, err = run_remote_cmd(client, migrate_cmd)
    print("=== MIGRATE OUTPUT ===")
    print(migrate_out)
    
    # Clear caches
    clear_cmd = f"cd {remote_base} && php artisan route:clear && php artisan config:clear && php artisan cache:clear"
    code, clear_out, err = run_remote_cmd(client, clear_cmd)
    print("=== CACHE CLEAR OUTPUT ===")
    print(clear_out)
    
    # Verify migration status
    code, status_out, err = run_remote_cmd(client, f"cd {remote_base} && php artisan migrate:status")
    print("=== MIGRATE STATUS (TAIL) ===")
    lines = status_out.strip().split('\n')
    print('\n'.join(lines[-15:]))
    
    client.close()

if __name__ == '__main__':
    main()
