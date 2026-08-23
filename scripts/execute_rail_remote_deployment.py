import paramiko
import json
import time

hostname = '76.13.79.242'
username = 'highest-ye'
password = 'YoK2PBV1fo82yujX2tDq'

print("=== STARTING CONTROLLED DEPLOYMENT FOR ORDER LIFECYCLE PIPELINE RAIL ===")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(hostname, username=username, password=password)

def run_ssh(cmd, cwd="/home/highest-ye/htdocs/highest-ye.store"):
    full_cmd = f"cd {cwd} && {cmd}"
    stdin, stdout, stderr = ssh.exec_command(full_cmd)
    out = stdout.read().decode('utf-8').strip()
    err = stderr.read().decode('utf-8').strip()
    return out, err

# Phase 0: Baseline checks
print("\n--- PHASE 0: PRE-DEPLOYMENT BASELINE CHECKS ---")
branch, _ = run_ssh("git branch --show-current")
head_pre, _ = run_ssh("git rev-parse HEAD")
status_pre, _ = run_ssh("git status --short")
print(f"Current Branch: {branch}")
print(f"Pre-deploy HEAD: {head_pre}")
print(f"Git Status: {status_pre if status_pre else 'CLEAN'}")

# Baseline Table Row Counts
tables = [
    'orders', 'order_items', 'product_inventories', 'inventory_movements',
    'purchase_orders', 'purchase_order_items', 'inbound_receipt_manifests',
    'inventory_transfer_manifests', 'delivery_assignments',
    'order_lifecycle_stage_views', 'order_item_lifecycle_stage_views'
]

tinker_count_cmd = "php artisan tinker --execute=\"" + "; ".join([f"echo '{t}:' . \\Illuminate\\Support\\Facades\\DB::table('{t}')->count() . '\\n'" for t in tables]) + "\""
counts_pre_raw, _ = run_ssh(tinker_count_cmd)
print("Baseline Row Counts:\n", counts_pre_raw)

# Phase 1 & 2: Git Fetch & Fast-Forward
print("\n--- PHASE 1 & 2: GIT FETCH & FAST-FORWARD TO 8a551ea ---")
run_ssh("git fetch --prune")

# Check composer.lock diff
composer_diff, _ = run_ssh("git diff HEAD..8a551eaa150c1f31ce174872b03b9b471d8b8b94 -- composer.lock")
if composer_diff:
    print("WARNING: composer.lock diff detected:\n", composer_diff)
else:
    print("composer.lock diff: ZERO (No composer install needed)")

# Fast-Forward Merge
ff_out, ff_err = run_ssh("git merge --ff-only 8a551eaa150c1f31ce174872b03b9b471d8b8b94")
print("Merge Output:", ff_out)
if ff_err:
    print("Merge Stderr:", ff_err)

head_post, _ = run_ssh("git rev-parse HEAD")
print(f"Post-deploy HEAD: {head_post}")

# Phase 3: Cache Update
print("\n--- PHASE 3: CACHE & OPTIMIZATION UPDATE ---")
opt_out, _ = run_ssh("php artisan optimize:clear")
cfg_out, _ = run_ssh("php artisan config:cache")
view_out, _ = run_ssh("php artisan view:cache")
print("Optimize Clear:", opt_out)
print("Config Cache:", cfg_out)
print("View Cache:", view_out)

# Phase 4: Post-Deployment Verification
print("\n--- PHASE 4: POST-DEPLOYMENT READ-ONLY VERIFICATION ---")

counts_post_raw, _ = run_ssh(tinker_count_cmd)
print("Post-Deploy Row Counts:\n", counts_post_raw)

# Test View Render via Tinker
tinker_render_cmd = """php artisan tinker --execute="
    \\$service = app(\\Webkul\\Admin\\Services\\HayestDashboardAggregationService::class);
    \\$data = \\$service->getAdvancedData();
    \\$html = view('admin::dashboard.advanced.index', ['advancedData' => \\$data])->render();
    echo 'RENDER_SUCCESS_LEN:' . strlen(\\$html) . '\\n';
    echo 'HAS_RAIL:' . (str_contains(\\$html, 'ORDER LIFECYCLE PIPELINE') ? 'YES' : 'NO') . '\\n';
" """
render_out, render_err = run_ssh(tinker_render_cmd)
print("View Render Test Result:\n", render_out)

ssh.close()
print("\n=== CONTROLLED DEPLOYMENT COMPLETED SUCCESSFULLY ===")
