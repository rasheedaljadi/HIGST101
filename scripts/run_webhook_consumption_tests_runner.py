import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    local_test_file = os.path.join(base_dir, 'scripts', 'run_webhook_consumption_tests.php')
    with open(local_test_file, 'r', encoding='utf-8') as f:
        content = f.read()
        
    sftp = client.open_sftp()
    with sftp.file('/tmp/run_wh_tests.php', 'w') as f:
        f.write(content)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/run_wh_tests.php && rm -f /tmp/run_wh_tests.php")
    print(out)
    client.close()

if __name__ == '__main__':
    main()
