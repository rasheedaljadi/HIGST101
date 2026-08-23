import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    cmd = f"ls -la {remote_base}/packages/Webkul/Procurement/src/Contracts {remote_base}/packages/Webkul/Procurement/src/DTO {remote_base}/packages/Webkul/Procurement/src/Gateways 2>&1"
    code, out, err = run_remote_cmd(client, cmd)
    print(out)
    client.close()

if __name__ == '__main__':
    main()
