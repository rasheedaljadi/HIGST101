import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    
    sftp = client.open_sftp()
    
    files_to_upload = [
        ('packages/Webkul/Procurement/src/Contracts/AliExpressOrderGateway.php', '/tmp/procurement_src/Contracts/AliExpressOrderGateway.php'),
        ('packages/Webkul/Procurement/src/DTO/ExternalOrderDraft.php', '/tmp/procurement_src/DTO/ExternalOrderDraft.php'),
        ('packages/Webkul/Procurement/src/DTO/AliExpressOrderPreflight.php', '/tmp/procurement_src/DTO/AliExpressOrderPreflight.php'),
        ('packages/Webkul/Procurement/src/DTO/AliExpressOrderSnapshot.php', '/tmp/procurement_src/DTO/AliExpressOrderSnapshot.php'),
        ('packages/Webkul/Procurement/src/DTO/VerifiedExternalOrderCreated.php', '/tmp/procurement_src/DTO/VerifiedExternalOrderCreated.php'),
        ('packages/Webkul/Procurement/src/DTO/ExternalOrderSubmissionFailed.php', '/tmp/procurement_src/DTO/ExternalOrderSubmissionFailed.php'),
        ('packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php', '/tmp/procurement_src/Gateways/AliExpressOrderSubmissionGateway.php'),
        ('packages/Webkul/Procurement/src/Models/AliExpressWebhookInboxMessage.php', '/tmp/procurement_src/Models/AliExpressWebhookInboxMessage.php'),
        ('packages/Webkul/Procurement/src/Jobs/ProcessAliExpressWebhookJob.php', '/tmp/procurement_src/Jobs/ProcessAliExpressWebhookJob.php'),
        ('packages/Webkul/Procurement/src/Database/Migrations/2026_08_22_000002_create_aliexpress_webhook_inbox_messages_table.php', '/tmp/procurement_src/Database/Migrations/2026_08_22_000002_create_aliexpress_webhook_inbox_messages_table.php'),
        ('app/Services/AliExpress/AliExpressWebhookSignatureVerifier.php', '/home/highest-ye/htdocs/highest-ye.store/app/Services/AliExpress/AliExpressWebhookSignatureVerifier.php'),
        ('app/Http/Controllers/AliExpress/AliExpressWebhookController.php', '/home/highest-ye/htdocs/highest-ye.store/app/Http/Controllers/AliExpress/AliExpressWebhookController.php'),
    ]
    
    run_remote_cmd(client, "mkdir -p /tmp/procurement_src/Contracts /tmp/procurement_src/DTO /tmp/procurement_src/Gateways /tmp/procurement_src/Models /tmp/procurement_src/Jobs /tmp/procurement_src/Database/Migrations /home/highest-ye/htdocs/highest-ye.store/app/Services/AliExpress")
    
    for local_rel, remote_abs in files_to_upload:
        local_path = os.path.join(base_dir, local_rel)
        if os.path.exists(local_path):
            with open(local_path, 'r', encoding='utf-8') as f:
                content = f.read()
            with sftp.file(remote_abs, 'w') as rf:
                rf.write(content)
                
    test_runner_path = os.path.join(base_dir, 'scripts', 'run_webhook_consumption_tests.php')
    with open(test_runner_path, 'r', encoding='utf-8') as f:
        runner_content = f.read()
    with sftp.file('/tmp/run_webhook_consumption_tests.php', 'w') as rf:
        rf.write(runner_content)
        
    sftp.close()
    
    cmd = "cd /home/highest-ye/htdocs/highest-ye.store && php /tmp/run_webhook_consumption_tests.php"
    code, out, err = run_remote_cmd(client, cmd)
    print("=== REMOTE TEST OUTPUT ===")
    print(out)
    if err:
        print("=== REMOTE TEST STDERR ===")
        print(err)
        
    client.close()

if __name__ == '__main__':
    main()
