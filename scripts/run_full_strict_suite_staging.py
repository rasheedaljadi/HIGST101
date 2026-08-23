import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    
    sftp = client.open_sftp()
    with open(os.path.join(base_dir, 'scripts/run_strict_gateway_correctness_tests.php'), 'r', encoding='utf-8') as f:
        sftp.file('/tmp/run_strict_gateway_tests.php', 'w').write(f.read())
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/run_strict_gateway_tests.php && rm -f /tmp/run_strict_gateway_tests.php")
    print("=== STRICT GATEWAY CORRECTNESS TEST SUITE ===")
    print(out)
    if err:
        print("ERR:", err)
        
    client.close()

if __name__ == '__main__':
    main()
