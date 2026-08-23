import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    target_sha = "0dd0a570d9391b973fb6241ace19d08b1b38d9a9"
    
    print(f"[Deploy] Fetching and checking out {target_sha} on Staging...")
    
    cmd_fetch = f"cd {remote_base} && git fetch origin feat/delivery-admin-ui-rebuild && git checkout FETCH_HEAD"
    code, out, err = run_remote_cmd(client, cmd_fetch)
    print(f"[Deploy Git Checkout]\n{out}\n{err}")
    
    cmd_verify = f"cd {remote_base} && git rev-parse HEAD"
    code, current_sha, _ = run_remote_cmd(client, cmd_verify)
    current_sha = current_sha.strip()
    print(f"[Deploy Current SHA] {current_sha}")
    
    if current_sha != target_sha:
        print(f"[ERROR] SHA mismatch: {current_sha} != {target_sha}")
        client.close()
        sys.exit(1)
        
    cmd_caches = f"cd {remote_base} && php artisan config:clear && php artisan route:clear && php artisan view:clear"
    code, out_cache, err_cache = run_remote_cmd(client, cmd_caches)
    print(f"[Deploy Caches Cleared]\n{out_cache}")
    
    client.close()
    print("[Deploy] Staging deployment completed successfully!")

if __name__ == '__main__':
    main()
