import json
import os
import sys

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    # Upload newly created files to staging temp location or test
    sftp = client.open_sftp()
    
    files_to_sync = [
        ('app/Services/AliExpress/DTO/ValidatedAliExpressShippingAddress.php', f'{remote_base}/app/Services/AliExpress/DTO/ValidatedAliExpressShippingAddress.php'),
        ('app/Services/AliExpress/Exceptions/AliExpressInvalidShippingAddressException.php', f'{remote_base}/app/Services/AliExpress/Exceptions/AliExpressInvalidShippingAddressException.php'),
        ('app/Services/AliExpress/Shipping/AliExpressShippingAddressValidator.php', f'{remote_base}/app/Services/AliExpress/Shipping/AliExpressShippingAddressValidator.php'),
        ('packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php', f'{remote_base}/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php'),
        ('packages/Webkul/Fulfillment/src/Providers/AliExpress/AliExpressFulfillmentProvider.php', f'{remote_base}/packages/Webkul/Fulfillment/src/Providers/AliExpress/AliExpressFulfillmentProvider.php'),
        ('app/Http/Controllers/AliExpress/AliExpressKeysController.php', f'{remote_base}/app/Http/Controllers/AliExpress/AliExpressKeysController.php'),
        ('scripts/run_address_guard_tests_isolated.php', f'{remote_base}/scripts/run_address_guard_tests_isolated.php'),
    ]
    
    for local_f, remote_f in files_to_sync:
        with open(local_f, 'r', encoding='utf-8') as lf:
            content = lf.read()
        # ensure remote dir
        remote_dir = os.path.dirname(remote_f).replace('\\', '/')
        run_remote_cmd(client, f"mkdir -p {remote_dir}")
        with sftp.open(remote_f, 'w') as rf:
            rf.write(content)
        print(f"[SSH] Synced: {local_f} -> {remote_f}")
        
    sftp.close()
    
    print("\n[SSH] Running isolated test suite on Staging...")
    cmd = f"cd {remote_base} && php scripts/run_address_guard_tests_isolated.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Output ---\n{out}")
    if err:
        print(f"\n--- STDERR ---\n{err}")
        
    client.close()

if __name__ == '__main__':
    main()
