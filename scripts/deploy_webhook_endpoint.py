import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    files = [
        ('app/Http/Controllers/AliExpress/AliExpressWebhookController.php', f'{remote_base}/app/Http/Controllers/AliExpress/AliExpressWebhookController.php'),
        ('routes/web.php', f'{remote_base}/routes/web.php'),
        ('bootstrap/app.php', f'{remote_base}/bootstrap/app.php'),
    ]
    
    sftp = client.open_sftp()
    for local_rel, remote_abs in files:
        local_path = os.path.join(base_dir, local_rel)
        with open(local_path, 'r', encoding='utf-8') as f:
            content = f.read()
        with sftp.file(remote_abs, 'w') as rf:
            rf.write(content)
        print(f"Uploaded: {local_rel} -> {remote_abs}")
    sftp.close()
    
    # Clear route and application cache on server
    cmd = f"cd {remote_base} && php artisan route:clear && php artisan config:clear && php artisan cache:clear"
    code, out, err = run_remote_cmd(client, cmd)
    print("Cache clear output:", out)
    
    # Test endpoint with curl
    test_cmd = "curl -i -X POST -H 'Content-Type: application/json' -d '{\"message_type\":0,\"seller_id\":\"test\"}' https://highest-ye.store/aliexpress/webhook"
    code, out, err = run_remote_cmd(client, test_cmd)
    print("Test POST result:")
    print(out)
    
    client.close()

if __name__ == '__main__':
    main()
