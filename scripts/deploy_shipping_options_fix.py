import os
import remote_ssh_helper as r

LOCAL_ROOT = r"e:\HIGESTO NEW1\higest\higest101"
REMOTE_ROOT = "/home/highest-ye/htdocs/highest-ye.store"

files_to_sync = [
    ("app/Http/Controllers/AliExpress/AliExpressKeysController.php", "app/Http/Controllers/AliExpress/AliExpressKeysController.php"),
    ("app/Jobs/Pricing/RecalculateCatalogPricesJob.php", "app/Jobs/Pricing/RecalculateCatalogPricesJob.php"),
    ("resources/views/aliexpress/keys.blade.php", "resources/views/aliexpress/keys.blade.php"),
    ("tests/Feature/AliExpress/AliExpressKeysControllerTest.php", "tests/Feature/AliExpress/AliExpressKeysControllerTest.php"),
]

client = r.get_ssh_client()
sftp = client.open_sftp()

print("[Deploy] Ensuring remote directories exist...")
remote_dirs = [
    f"{REMOTE_ROOT}/app/Jobs/Pricing",
    f"{REMOTE_ROOT}/app/Http/Controllers/AliExpress",
    f"{REMOTE_ROOT}/resources/views/aliexpress",
    f"{REMOTE_ROOT}/tests/Feature/AliExpress",
]
for rd in remote_dirs:
    try:
        sftp.mkdir(rd)
        print(f"[Deploy] Created dir: {rd}")
    except Exception:
        pass

for local_rel, remote_rel in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, local_rel)
    remote_path = f"{REMOTE_ROOT}/{remote_rel}"
    print(f"[Deploy] Uploading {local_rel} -> {remote_path}")
    sftp.put(local_path, remote_path)

sftp.close()

print("[Deploy] Clearing remote caches...")
cmds = [
    f"cd {REMOTE_ROOT} && php8.4 artisan view:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan cache:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan config:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan route:clear",
]

for cmd in cmds:
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"CMD: {cmd} => EXIT: {code}")
    if out:
        print(f"OUT: {out}")
    if err:
        print(f"ERR: {err}")

client.close()
print("[Deploy] Done!")
