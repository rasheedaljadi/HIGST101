import os
import remote_ssh_helper as r

LOCAL_ROOT = r"e:\HIGESTO NEW1\higest\higest101"
REMOTE_ROOT = "/home/highest-ye/htdocs/highest-ye.store"

files_to_sync = [
    ("packages/Webkul/Admin/src/Routes/dropshipping-routes.php", "packages/Webkul/Admin/src/Routes/dropshipping-routes.php"),
    ("packages/Webkul/DeliveryManagement/src/Routes/delivery-routes.php", "packages/Webkul/DeliveryManagement/src/Routes/delivery-routes.php"),
    ("packages/Webkul/DeliveryManagement/src/Http/Controllers/Admin/DeliveryDashboardController.php", "packages/Webkul/DeliveryManagement/src/Http/Controllers/Admin/DeliveryDashboardController.php"),
    ("packages/Webkul/Wallet/src/Routes/admin-wallet-routes.php", "packages/Webkul/Wallet/src/Routes/admin-wallet-routes.php"),
    ("packages/Webkul/Wallet/src/Http/Controllers/Admin/WalletDashboardController.php", "packages/Webkul/Wallet/src/Http/Controllers/Admin/WalletDashboardController.php"),
]

client = r.get_ssh_client()
sftp = client.open_sftp()

print("[Deploy] Uploading updated route and controller files...")
for local_rel, remote_rel in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, local_rel)
    remote_path = f"{REMOTE_ROOT}/{remote_rel}"
    print(f"  -> {local_rel}")
    sftp.put(local_path, remote_path)

sftp.close()

print("\n=======================================================")
print("1. AUDITING & FIXING STORAGE & CACHE PERMISSIONS")
print("=======================================================")
perm_cmds = [
    f"cd {REMOTE_ROOT} && chmod -R 775 storage bootstrap/cache",
    f"cd {REMOTE_ROOT} && ls -ld storage storage/framework/cache storage/framework/views bootstrap/cache",
]
for cmd in perm_cmds:
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"CMD: {cmd}\nOUT: {out}")

print("\n=======================================================")
print("2. TESTING ALL CACHE CLEARING COMMANDS")
print("=======================================================")
clear_cmds = [
    f"cd {REMOTE_ROOT} && php8.4 artisan optimize:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan config:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan cache:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan clear-compiled",
    f"cd {REMOTE_ROOT} && php8.4 artisan event:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan route:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan view:clear",
]
for cmd in clear_cmds:
    code, out, err = r.run_remote_cmd(client, cmd)
    status = "SUCCESS" if code == 0 else f"FAILED (code {code})"
    print(f"[{status}] {cmd}\n  -> {out.strip()}")
    if err:
        print(f"  ERR: {err.strip()}")

print("\n=======================================================")
print("3. TESTING ALL CACHE BUILDING & OPTIMIZATION COMMANDS")
print("=======================================================")
build_cmds = [
    f"cd {REMOTE_ROOT} && php8.4 artisan config:cache",
    f"cd {REMOTE_ROOT} && php8.4 artisan route:cache",
    f"cd {REMOTE_ROOT} && php8.4 artisan view:cache",
    f"cd {REMOTE_ROOT} && php8.4 artisan event:cache",
    f"cd {REMOTE_ROOT} && php8.4 artisan optimize",
]
for cmd in build_cmds:
    code, out, err = r.run_remote_cmd(client, cmd)
    status = "SUCCESS" if code == 0 else f"FAILED (code {code})"
    print(f"[{status}] {cmd}\n  -> {out.strip()}")
    if err:
        print(f"  ERR: {err.strip()}")

client.close()
print("\n[Audit] Full Caching Verification Finished!")
