import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    code, out, err = run_remote_cmd(client, f"cd {remote_base} && php artisan view:clear && php artisan cache:clear")
    print(out)
    if err:
        print("[ERR]", err)
    client.close()

if __name__ == '__main__':
    main()
