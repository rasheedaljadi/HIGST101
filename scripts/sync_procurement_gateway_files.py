import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    files = [
        ('packages/Webkul/Procurement/src/Contracts/AliExpressOrderGateway.php', f'{remote_base}/packages/Webkul/Procurement/src/Contracts/AliExpressOrderGateway.php'),
        ('packages/Webkul/Procurement/src/DTO/ExternalOrderDraft.php', f'{remote_base}/packages/Webkul/Procurement/src/DTO/ExternalOrderDraft.php'),
        ('packages/Webkul/Procurement/src/DTO/AliExpressOrderPreflight.php', f'{remote_base}/packages/Webkul/Procurement/src/DTO/AliExpressOrderPreflight.php'),
        ('packages/Webkul/Procurement/src/DTO/AliExpressOrderSnapshot.php', f'{remote_base}/packages/Webkul/Procurement/src/DTO/AliExpressOrderSnapshot.php'),
        ('packages/Webkul/Procurement/src/DTO/VerifiedExternalOrderCreated.php', f'{remote_base}/packages/Webkul/Procurement/src/DTO/VerifiedExternalOrderCreated.php'),
        ('packages/Webkul/Procurement/src/DTO/ExternalOrderSubmissionFailed.php', f'{remote_base}/packages/Webkul/Procurement/src/DTO/ExternalOrderSubmissionFailed.php'),
        ('packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php', f'{remote_base}/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php'),
        ('packages/Webkul/Procurement/src/Providers/ProcurementServiceProvider.php', f'{remote_base}/packages/Webkul/Procurement/src/Providers/ProcurementServiceProvider.php'),
        ('packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php', f'{remote_base}/packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php'),
    ]
    
    run_remote_cmd(client, f"mkdir -p {remote_base}/packages/Webkul/Procurement/src/Contracts {remote_base}/packages/Webkul/Procurement/src/DTO {remote_base}/packages/Webkul/Procurement/src/Gateways {remote_base}/packages/Webkul/Procurement/src/Providers {remote_base}/packages/Webkul/Procurement/src/Services")
    
    sftp = client.open_sftp()
    for local_rel, remote_abs in files:
        local_path = os.path.join(base_dir, local_rel)
        with open(local_path, 'r', encoding='utf-8') as f:
            content = f.read()
        with sftp.file(remote_abs, 'w') as rf:
            rf.write(content)
        print(f"Uploaded: {local_rel}")
    sftp.close()
    
    # Flush failed jobs table for clean test
    run_remote_cmd(client, f"cd {remote_base} && php artisan queue:flush && php artisan queue:restart")
    
    # Restart worker service
    run_remote_cmd(client, "systemctl --user restart highest-queue-aliexpress-webhooks.service")
    
    client.close()

if __name__ == '__main__':
    main()
