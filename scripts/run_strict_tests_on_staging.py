import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    files = [
        ('packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php', f'{remote_base}/packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php'),
        ('packages/Webkul/Procurement/src/DTO/AliExpressOrderPreflight.php', f'{remote_base}/packages/Webkul/Procurement/src/DTO/AliExpressOrderPreflight.php'),
        ('packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php', f'{remote_base}/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php'),
        ('scripts/run_strict_gateway_correctness_tests.php', '/tmp/run_strict_gateway_tests.php'),
    ]
    
    run_remote_cmd(client, f"mkdir -p {remote_base}/packages/Webkul/Procurement/src/Support")
    
    sftp = client.open_sftp()
    for local_rel, remote_abs in files:
        local_path = os.path.join(base_dir, local_rel)
        with open(local_path, 'r', encoding='utf-8') as f:
            content = f.read()
        with sftp.file(remote_abs, 'w') as rf:
            rf.write(content)
        print(f"Uploaded: {local_rel} -> {remote_abs}")
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/run_strict_gateway_tests.php && rm -f /tmp/run_strict_gateway_tests.php")
    print(out)
    if err:
        print("ERR:", err)
        
    client.close()

if __name__ == '__main__':
    main()
