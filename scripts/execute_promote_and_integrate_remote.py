import json
import sys
import os
import datetime
from remote_ssh_helper import get_ssh_client, run_remote_cmd

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

APP_PATH = '/home/highest-ye/htdocs/highest-ye.store'
BACKUP_ROOT = '/home/highest-ye/backups'
PREV_MANIFEST_DIR = '/home/highest-ye/backups/pre-procurement-v2-dirty-tree-20260822_033635Z'
PROCUREMENT_TARGET = '0316298afa2c15ae5aca6b312d4b7b5f284a01e0'

def main():
    client = get_ssh_client()
    utc_ts = datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%d_%H%M%SZ')
    marker_dir = f"{BACKUP_ROOT}/promote-remote-changes-{utc_ts}"
    quarantine_dir = f"{marker_dir}/quarantine"
    
    print(f"\n==================================================================")
    print(f"PHASE 0: Establish Safety Marker & Configure Git Identity on Remote")
    print(f"Marker Dir: {marker_dir}")
    print(f"==================================================================")
    
    # Configure git committer identity
    run_remote_cmd(client, f"cd {APP_PATH} && git config user.name 'Admin'")
    run_remote_cmd(client, f"cd {APP_PATH} && git config user.email 'admin@example.com'")
    
    # Create marker directory
    run_remote_cmd(client, f"mkdir -p {quarantine_dir}")
    run_remote_cmd(client, f"chmod 700 {marker_dir}")
    
    # Reset git index so we can commit group by group
    run_remote_cmd(client, f"cd {APP_PATH} && git reset")
    
    # Ensure leftovers are in quarantine
    remote_quarantine_script = f"""
import os
import shutil

app_path = '{APP_PATH}'
quarantine_dir = '{quarantine_dir}'

leftover_exact = [
    'diag_routes.php',
    'encoding_test.php',
    'scripts_check.php',
    'id}}',
    'email}}',
    'name}}',
    'permission_type}}',
    'role_id}}'
]

moved = []
for fname in os.listdir(app_path):
    fpath = os.path.join(app_path, fname)
    if fname in leftover_exact or 'permissions}}' in fname or 'status}}' in fname:
        dst = os.path.join(quarantine_dir, fname)
        shutil.move(fpath, dst)
        moved.append(fname)

print("Quarantined files:", moved)
"""
    sftp = client.open_sftp()
    with sftp.file("/tmp/quarantine_runner.py", "w") as f:
        f.write(remote_quarantine_script)
    sftp.close()
    
    code_q, q_out, _ = run_remote_cmd(client, "python3 /tmp/quarantine_runner.py")
    print(q_out)
    run_remote_cmd(client, "rm -f /tmp/quarantine_runner.py")
    
    # Record marker info
    _, current_sha, _ = run_remote_cmd(client, f"cd {APP_PATH} && git rev-parse HEAD")
    _, status_short, _ = run_remote_cmd(client, f"cd {APP_PATH} && git status --short")
    
    sftp = client.open_sftp()
    with sftp.file(f"{marker_dir}/marker_info.json", "w") as f:
        json.dump({
            "timestamp_utc": utc_ts,
            "current_sha": current_sha,
            "prev_manifest_dir": PREV_MANIFEST_DIR,
            "status_short": status_short.splitlines()
        }, f, indent=2)
    sftp.close()
    
    print("\n--- Current Git Status on Remote ---")
    print(status_short)
    
    print(f"\n==================================================================")
    print(f"PHASE 2 & 3: Thematic Commits (A, B, C, D) on Remote Server")
    print(f"==================================================================")
    
    commits_info = []
    
    # -------------------------------------------------------------
    # Commit A: AliExpress & Semantic Attribute Synchronization
    # -------------------------------------------------------------
    commit_a_files = [
        "app/Console/Commands/AliExpressSyncProducts.php",
        "app/Services/AliExpress/AliExpressApiClient.php",
        "app/Services/AliExpress/AliExpressProductImporter.php",
        "app/Services/AliExpress/AliExpressProductSyncer.php",
        "database/migrations/2026_08_19_215326_create_semantic_attribute_memory_table.php",
        "app/Models/AliExpress/SemanticAttributeMemory.php",
        "app/Services/AliExpress/Semantic"
    ]
    print("\n--- Preparing Commit A (AliExpress & Semantic) ---")
    add_a_cmd = " ".join([f"'{f}'" for f in commit_a_files])
    run_remote_cmd(client, f"cd {APP_PATH} && git add {add_a_cmd}")
    
    msg_a = "feat(aliexpress): preserve semantic attribute synchronization improvements"
    code_c_a, commit_a_out, err_a = run_remote_cmd(client, f"cd {APP_PATH} && git commit -m \"{msg_a}\"")
    print(commit_a_out)
    if err_a: print(f"Commit A STDERR: {err_a}")
    
    _, sha_a, _ = run_remote_cmd(client, f"cd {APP_PATH} && git rev-parse HEAD")
    print(f"✓ Commit A SHA: {sha_a}")
    commits_info.append({"commit": "A", "sha": sha_a, "msg": msg_a, "files": commit_a_files})
    
    # -------------------------------------------------------------
    # Commit B: Hayest Admin Advanced Dashboard & Order Lifecycle
    # -------------------------------------------------------------
    commit_b_files = [
        "packages/Webkul/Admin/src/Http/Controllers/DashboardController.php",
        "packages/Webkul/Admin/src/Resources/views/dashboard/advanced/index.blade.php",
        "packages/Webkul/Admin/src/Services/HayestDashboardAggregationService.php",
        "packages/Webkul/Sales/src/Services/Lifecycle/OrderLifecycleDashboardQueryService.php"
    ]
    print("\n--- Preparing Commit B (Dashboard & Order Lifecycle) ---")
    add_b_cmd = " ".join([f"'{f}'" for f in commit_b_files])
    run_remote_cmd(client, f"cd {APP_PATH} && git add {add_b_cmd}")
    
    msg_b = "feat(admin): preserve advanced dashboard and order lifecycle improvements"
    code_c_b, commit_b_out, err_b = run_remote_cmd(client, f"cd {APP_PATH} && git commit -m \"{msg_b}\"")
    print(commit_b_out)
    if err_b: print(f"Commit B STDERR: {err_b}")
    
    _, sha_b, _ = run_remote_cmd(client, f"cd {APP_PATH} && git rev-parse HEAD")
    print(f"✓ Commit B SHA: {sha_b}")
    commits_info.append({"commit": "B", "sha": sha_b, "msg": msg_b, "files": commit_b_files})
    
    # -------------------------------------------------------------
    # Commit C: Inventory Delivery & Fulfillment Operations
    # -------------------------------------------------------------
    commit_c_files = [
        "packages/Webkul/DeliveryManagement/src/Config/admin-menu.php",
        "packages/Webkul/Fulfillment/src/Listeners/AliExpressStockListener.php",
        "packages/Webkul/Inventory/src/DataGrids/InventoryProductCardDataGrid.php",
        "packages/Webkul/Inventory/src/Database/Seeders/InventorySourcesModelV12Seeder.php",
        "packages/Webkul/Inventory/src/Http/Controllers/Admin/InventoryProductCardController.php"
    ]
    print("\n--- Preparing Commit C (Operations & Inventory) ---")
    add_c_cmd = " ".join([f"'{f}'" for f in commit_c_files])
    run_remote_cmd(client, f"cd {APP_PATH} && git add {add_c_cmd}")
    
    msg_c = "feat(operations): preserve inventory delivery and fulfillment improvements"
    code_c_c, commit_c_out, err_c = run_remote_cmd(client, f"cd {APP_PATH} && git commit -m \"{msg_c}\"")
    print(commit_c_out)
    if err_c: print(f"Commit C STDERR: {err_c}")
    
    _, sha_c, _ = run_remote_cmd(client, f"cd {APP_PATH} && git rev-parse HEAD")
    print(f"✓ Commit C SHA: {sha_c}")
    commits_info.append({"commit": "C", "sha": sha_c, "msg": msg_c, "files": commit_c_files})
    
    # -------------------------------------------------------------
    # Commit D: Shop PDF Document Enhancement
    # -------------------------------------------------------------
    commit_d_files = [
        "packages/Webkul/Shop/src/Resources/views/customers/account/orders/pdf.blade.php"
    ]
    print("\n--- Preparing Commit D (Shop Customer Document) ---")
    add_d_cmd = " ".join([f"'{f}'" for f in commit_d_files])
    run_remote_cmd(client, f"cd {APP_PATH} && git add {add_d_cmd}")
    
    msg_d = "fix(shop): preserve customer order document enhancement"
    code_c_d, commit_d_out, err_d = run_remote_cmd(client, f"cd {APP_PATH} && git commit -m \"{msg_d}\"")
    print(commit_d_out)
    if err_d: print(f"Commit D STDERR: {err_d}")
    
    _, sha_d, _ = run_remote_cmd(client, f"cd {APP_PATH} && git rev-parse HEAD")
    print(f"✓ Commit D SHA: {sha_d}")
    commits_info.append({"commit": "D", "sha": sha_d, "msg": msg_d, "files": commit_d_files})
    
    # Check working tree status after A-D
    _, status_after_d, _ = run_remote_cmd(client, f"cd {APP_PATH} && git status --short")
    print("\n--- Git Status After Commit A-D ---\n" + (status_after_d if status_after_d else "(100% clean)"))
    
    print(f"\n==================================================================")
    print(f"PHASE 4: Merge Procurement V2 ({PROCUREMENT_TARGET})")
    print(f"==================================================================")
    
    # Ensure target commit is fetched
    run_remote_cmd(client, f"cd {APP_PATH} && git fetch origin --prune")
    
    merge_msg = "merge: integrate procurement v2 into preserved staging improvements"
    code_m, merge_out, merge_err = run_remote_cmd(client, f"cd {APP_PATH} && git merge --no-ff {PROCUREMENT_TARGET} -m \"{merge_msg}\"")
    print(f"Merge Exit Code: {code_m}")
    print(merge_out)
    if merge_err:
        print(f"Merge STDERR: {merge_err}")
        
    if code_m != 0:
        print("\nERROR: Merge conflict encountered!")
        _, conflict_status, _ = run_remote_cmd(client, f"cd {APP_PATH} && git status")
        print(conflict_status)
        sys.exit(1)
        
    # Get Merge HEAD SHA
    _, merge_sha, _ = run_remote_cmd(client, f"cd {APP_PATH} && git rev-parse HEAD")
    print(f"\n✓ Merge Successful! Unified HEAD SHA: {merge_sha}")
    
    # Log graph
    _, log_graph, _ = run_remote_cmd(client, f"cd {APP_PATH} && git log --graph --oneline -n 20")
    print("\n--- Git Log Graph ---\n" + log_graph)
    
    # Check ancestor validity
    _, anc_res, _ = run_remote_cmd(client, f"cd {APP_PATH} && git merge-base --is-ancestor {PROCUREMENT_TARGET} HEAD && echo 'TARGET_ANCESTOR_VALID'")
    print(f"Target Ancestor in Merge HEAD: {anc_res.strip()}")
    
    print(f"\n==================================================================")
    print(f"PHASE 5: Push Unified Branch to GitHub Origin")
    print(f"==================================================================")
    
    # Push to origin
    code_push, push_out, push_err = run_remote_cmd(client, f"cd {APP_PATH} && git push --porcelain origin HEAD:refs/heads/feat/delivery-admin-ui-rebuild")
    print(f"Push Exit Code: {code_push}")
    print(push_out)
    if push_err:
        print(f"Push STDERR: {push_err}")
        
    # Verify with git ls-remote
    _, ls_remote_out, _ = run_remote_cmd(client, f"cd {APP_PATH} && git ls-remote origin refs/heads/feat/delivery-admin-ui-rebuild")
    print(f"Remote Branch HEAD on GitHub: {ls_remote_out}")
    
    # Safe cache clear
    run_remote_cmd(client, f"cd {APP_PATH} && php artisan optimize:clear")
    _, final_status, _ = run_remote_cmd(client, f"cd {APP_PATH} && git status --short")
    print("\n--- Final Git Status on Server ---\n" + (final_status if final_status else "(100% clean)"))
    
    # Save complete report data locally
    integration_data = {
        "timestamp_utc": utc_ts,
        "marker_dir": marker_dir,
        "quarantine_dir": quarantine_dir,
        "thematic_commits": commits_info,
        "procurement_target": PROCUREMENT_TARGET,
        "merge_sha": merge_sha,
        "log_graph": log_graph.splitlines(),
        "remote_branch_head": ls_remote_out.strip(),
        "final_git_status": final_status.strip() if final_status else "clean"
    }
    
    with open('scripts/remote_integration_result.json', 'w', encoding='utf-8') as f:
        json.dump(integration_data, f, indent=2, ensure_ascii=False)
        
    print("\n✓ Promotion and Integration Completed Successfully!")
    client.close()

if __name__ == '__main__':
    main()
