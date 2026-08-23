import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    cmd = f"cd {remote_base} && php -d display_errors=1 scripts/run_address_guard_tests_isolated.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"Exit code: {code}")
    print(f"STDOUT:\n{out}")
    print(f"STDERR:\n{err}")
    
    client.close()

if __name__ == '__main__':
    main()
