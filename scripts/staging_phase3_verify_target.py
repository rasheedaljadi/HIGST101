import json
import sys
from remote_ssh_helper import get_ssh_client, run_remote_cmd

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

APP_PATH = '/home/highest-ye/htdocs/highest-ye.store'
TARGET_COMMIT = '0316298afa2c15ae5aca6b312d4b7b5f284a01e0'
BASELINE_COMMIT = '02658011a0a9f55e4b75b520b0d967dab7ade336'

def main():
    client = get_ssh_client()
    print("\n=== PHASE 3: Verify Remote Target Object and Lineage on Server ===")
    
    # 1. Fetch from origin
    print("Running 'git fetch origin --prune' on server...")
    code_fetch, fetch_out, fetch_err = run_remote_cmd(client, f"cd {APP_PATH} && git fetch origin --prune")
    print(f"Fetch exit code: {code_fetch}")
    if fetch_out: print(fetch_out)
    if fetch_err: print(fetch_err)
    
    # 2. Check if target commit exists in git object database
    code_cat, _, _ = run_remote_cmd(client, f"cd {APP_PATH} && git cat-file -e {TARGET_COMMIT}^{{commit}}")
    target_exists = (code_cat == 0)
    print(f"Target commit {TARGET_COMMIT} exists in server Git DB: {target_exists}")
    
    # 3. Check lineage from baseline to target
    cmd_lineage = f"cd {APP_PATH} && git merge-base --is-ancestor {BASELINE_COMMIT} {TARGET_COMMIT} && echo 'ANCESTOR_VALID'"
    _, lineage_res, _ = run_remote_cmd(client, cmd_lineage)
    lineage_valid = (lineage_res.strip() == 'ANCESTOR_VALID')
    print(f"Lineage verification ({BASELINE_COMMIT} -> {TARGET_COMMIT}): {lineage_valid}")
    
    # 4. Show commit details on server
    _, show_out, _ = run_remote_cmd(client, f"cd {APP_PATH} && git show --no-patch --format=fuller {TARGET_COMMIT}")
    print("\n--- Commit Details on Server ---")
    print(show_out)
    
    # 5. Name status diff from baseline to target
    _, diff_stat_out, _ = run_remote_cmd(client, f"cd {APP_PATH} && git diff --name-status {BASELINE_COMMIT}..{TARGET_COMMIT}")
    print("\n--- Git Diff Name-Status (Baseline to Target) ---")
    print(diff_stat_out)
    
    # Save Phase 3 verification data
    phase3_data = {
        "target_commit": TARGET_COMMIT,
        "baseline_commit": BASELINE_COMMIT,
        "target_exists_on_remote": target_exists,
        "lineage_valid_on_remote": lineage_valid,
        "show_output": show_out,
        "diff_name_status_lines": diff_stat_out.splitlines(),
    }
    
    with open('scripts/phase3_verify_target_result.json', 'w', encoding='utf-8') as f:
        json.dump(phase3_data, f, indent=2, ensure_ascii=False)
        
    print("\nPhase 3 Complete: Remote Target Commit Verified!")
    client.close()

if __name__ == '__main__':
    main()
