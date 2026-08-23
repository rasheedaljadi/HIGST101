import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    
    # 1. Upload newly created gateway & DTO files to /tmp/procurement_src
    sftp = client.open_sftp()
    
    files_to_upload = [
        ('packages/Webkul/Procurement/src/Contracts/AliExpressOrderGateway.php', '/tmp/procurement_src/Contracts/AliExpressOrderGateway.php'),
        ('packages/Webkul/Procurement/src/DTO/ExternalOrderDraft.php', '/tmp/procurement_src/DTO/ExternalOrderDraft.php'),
        ('packages/Webkul/Procurement/src/DTO/AliExpressOrderPreflight.php', '/tmp/procurement_src/DTO/AliExpressOrderPreflight.php'),
        ('packages/Webkul/Procurement/src/DTO/AliExpressOrderSnapshot.php', '/tmp/procurement_src/DTO/AliExpressOrderSnapshot.php'),
        ('packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php', '/tmp/procurement_src/Gateways/AliExpressOrderSubmissionGateway.php'),
    ]
    
    run_remote_cmd(client, "mkdir -p /tmp/procurement_src/Contracts /tmp/procurement_src/DTO /tmp/procurement_src/Gateways")
    
    for local_rel, remote_abs in files_to_upload:
        local_path = os.path.join(base_dir, local_rel)
        if os.path.exists(local_path):
            with open(local_path, 'r', encoding='utf-8') as f:
                content = f.read()
            with sftp.file(remote_abs, 'w') as rf:
                rf.write(content)
                
    test_runner_path = os.path.join(base_dir, 'scripts', 'run_gateway_tests_isolated.php')
    with open(test_runner_path, 'r', encoding='utf-8') as f:
        runner_content = f.read()
    with sftp.file('/tmp/run_gateway_tests_isolated.php', 'w') as rf:
        rf.write(runner_content)
        
    sftp.close()
    
    cmd = "cd /home/highest-ye/htdocs/highest-ye.store && php /tmp/run_gateway_tests_isolated.php"
    code, out, err = run_remote_cmd(client, cmd)
    print("=== REMOTE TEST OUTPUT ===")
    print(out)
    if err:
        print("=== REMOTE TEST STDERR ===")
        print(err)
        
    client.close()

if __name__ == '__main__':
    main()
