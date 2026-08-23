import json
import sys
from remote_ssh_helper import get_ssh_client, run_remote_cmd

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

APP_PATH = '/home/highest-ye/htdocs/highest-ye.store'
BACKUP_ROOT = '/home/highest-ye/backups'

REMOTE_RUNNER_SCRIPT = r"""
import subprocess
import os
import hashlib
import json
import datetime

app_path = '/home/highest-ye/htdocs/highest-ye.store'
backup_root = '/home/highest-ye/backups'
utc_ts = datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%d_%H%M%SZ')
backup_dir = f"{backup_root}/pre-procurement-v2-dirty-tree-{utc_ts}"
patches_dir = f"{backup_dir}/patches"

os.makedirs(patches_dir, exist_ok=True)
os.chmod(backup_dir, 0o700)

# 1. Get git status porcelain -z
res = subprocess.run(["git", "-C", app_path, "status", "--porcelain", "-z"], capture_output=True)
raw = res.stdout

# Parse NUL-separated porcelain output
entries = []
i = 0
items = raw.split(b'\0')
for item in items:
    if not item:
        continue
    status_code = item[:2].decode('utf-8', errors='replace').strip()
    rel_path = item[3:].decode('utf-8', errors='replace')
    
    full_path = os.path.join(app_path, rel_path)
    
    # Classification
    if rel_path.startswith("database/migrations/"):
        cls = "MIGRATION"
    elif "AliExpress" in rel_path:
        cls = "FEATURE_ALIEXPRESS"
    elif "Dashboard" in rel_path:
        cls = "FEATURE_DASHBOARD"
    elif "Delivery" in rel_path:
        cls = "FEATURE_DELIVERY"
    elif "Inventory" in rel_path:
        cls = "FEATURE_INVENTORY"
    elif "Fulfillment" in rel_path:
        cls = "FEATURE_FULFILLMENT"
    elif "Sales" in rel_path:
        cls = "FEATURE_SALES"
    elif "Shop" in rel_path:
        cls = "FEATURE_SHOP"
    elif rel_path.startswith("storage/framework/views"):
        cls = "CACHE_GENERATED"
    elif rel_path.endswith(".php") and ("diag" in rel_path or "test" in rel_path or "check" in rel_path):
        cls = "DIAGNOSTIC_SCRIPT"
    elif rel_path.startswith("id}") or rel_path.startswith("email}") or rel_path.startswith("name}") or rel_path.startswith("permission_type}") or rel_path.startswith("role_id}") or "permissions}" in rel_path or "status}" in rel_path:
        cls = "SHELL_LEFTOVER_ARTIFACT"
    else:
        cls = "UNTRACKED_OR_OTHER"
        
    size_bytes = 0
    mtime = 0
    file_sha256 = "N/A"
    patch_rel = None
    
    if os.path.isfile(full_path):
        st = os.stat(full_path)
        size_bytes = st.st_size
        mtime = int(st.st_mtime)
        try:
            with open(full_path, 'rb') as f:
                file_sha256 = hashlib.sha256(f.read()).hexdigest()
        except Exception:
            file_sha256 = "ERROR"
            
        if status_code in ['M', 'MM']:
            safe_name = rel_path.replace('/', '__').replace('\\', '__') + ".patch"
            patch_file_full = os.path.join(patches_dir, safe_name)
            p_res = subprocess.run(["git", "-C", app_path, "diff", "--binary", "--", rel_path], capture_output=True)
            with open(patch_file_full, 'wb') as pf:
                pf.write(p_res.stdout)
            patch_rel = f"patches/{safe_name}"
            
    elif os.path.isdir(full_path):
        cls = "CACHE_GENERATED_DIR" if "storage" in rel_path else "DIRECTORY"
        file_sha256 = "DIRECTORY"
        
    entries.append({
        "path": rel_path,
        "status": status_code,
        "size_bytes": size_bytes,
        "mtime": mtime,
        "sha256": file_sha256,
        "classification": cls,
        "patch_file": patch_rel,
        "review_status": "TRACKED_PATCH_PRESERVED" if patch_rel else "NEEDS_OWNER_REVIEW"
    })

# Write manifest.txt and sha256.txt
manifest_lines = [
    f"# Remote Dirty Tree Manifest — Created {utc_ts} UTC",
    f"# Host: srv1697338 | App: {app_path}",
    f"# Git Baseline: 02658011a0a9f55e4b75b520b0d967dab7ade336",
    f"# Total Dirty Paths: {len(entries)}",
    "# -----------------------------------------------------------------------------",
    f"{'STATUS':<6} {'SIZE(B)':<10} {'CLASSIFICATION':<25} {'PATH':<60} {'SHA256'}",
    "# -----------------------------------------------------------------------------",
]

sha256_lines = []
for e in entries:
    manifest_lines.append(f"{e['status']:<6} {e['size_bytes']:<10} {e['classification']:<25} {e['path']:<60} {e['sha256']}")
    if e['sha256'] not in ['N/A', 'DIRECTORY', 'ERROR']:
        sha256_lines.append(f"{e['sha256']}  {e['path']}")

with open(f"{backup_dir}/manifest.txt", "w", encoding='utf-8') as f:
    f.write("\n".join(manifest_lines) + "\n")
    
with open(f"{backup_dir}/sha256.txt", "w", encoding='utf-8') as f:
    f.write("\n".join(sha256_lines) + "\n")
    
with open(f"{backup_dir}/manifest.json", "w", encoding='utf-8') as f:
    json.dump(entries, f, indent=2)

print(json.dumps({
    "backup_dir": backup_dir,
    "total_entries": len(entries),
    "entries": entries
}))
"""

def main():
    client = get_ssh_client()
    print("\n=== Uploading and executing Phase 2 dirty tree audit runner on remote ===")
    
    # Upload runner script to /tmp/phase2_dirty_tree_audit_runner.py
    sftp = client.open_sftp()
    with sftp.file('/tmp/phase2_dirty_tree_audit_runner.py', 'w') as f:
        f.write(REMOTE_RUNNER_SCRIPT)
    sftp.close()
    
    # Run the runner script with python3
    code, out, err = run_remote_cmd(client, "python3 /tmp/phase2_dirty_tree_audit_runner.py")
    if err:
        print(f"Runner STDERR:\n{err}")
        
    try:
        data = json.loads(out)
        backup_dir = data['backup_dir']
        total_entries = data['total_entries']
        entries = data['entries']
        
        print(f"\n✓ Successfully audited and preserved {total_entries} dirty paths!")
        print(f"Backup Artifact Directory: {backup_dir}")
        
        # Verify backup directory listing
        _, ls_out, _ = run_remote_cmd(client, f"ls -la {backup_dir} && ls -la {backup_dir}/patches")
        print("\n--- Remote Backup Directory Listing ---")
        print(ls_out)
        
        # Save local copy
        with open('scripts/remote_dirty_tree_manifest.json', 'w', encoding='utf-8') as f:
            json.dump(entries, f, indent=2, ensure_ascii=False)
            
        with open('scripts/remote_dirty_tree_backup_info.json', 'w', encoding='utf-8') as f:
            json.dump({
                "backup_dir": backup_dir,
                "total_entries": total_entries,
                "entries": entries
            }, f, indent=2, ensure_ascii=False)
            
        # Clean up runner script
        run_remote_cmd(client, "rm -f /tmp/phase2_dirty_tree_audit_runner.py")
        
    except Exception as e:
        print(f"Failed to parse runner output: {e}\nRaw Output:\n{out}")
        
    client.close()

if __name__ == '__main__':
    main()
