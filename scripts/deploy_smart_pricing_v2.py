import os
import remote_ssh_helper as r

LOCAL_ROOT = r"e:\HIGESTO NEW1\higest\higest101"
REMOTE_ROOT = "/home/highest-ye/htdocs/highest-ye.store"

files_to_sync = [
    ("app/Services/Pricing/CatalogPriceWriter.php", "app/Services/Pricing/CatalogPriceWriter.php"),
    ("packages/Webkul/Shop/src/Transformers/ProductPDPTransformer.php", "packages/Webkul/Shop/src/Transformers/ProductPDPTransformer.php"),
    ("app/Jobs/Pricing/RecalculateCatalogPricesJob.php", "app/Jobs/Pricing/RecalculateCatalogPricesJob.php"),
    ("app/Http/Controllers/AliExpress/AliExpressKeysController.php", "app/Http/Controllers/AliExpress/AliExpressKeysController.php"),
]

client = r.get_ssh_client()
sftp = client.open_sftp()

for local_rel, remote_rel in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, local_rel)
    remote_path = f"{REMOTE_ROOT}/{remote_rel}"
    print(f"[Deploy] Uploading {local_rel} -> {remote_path}")
    sftp.put(local_path, remote_path)

sftp.close()

cmds = [
    f"cd {REMOTE_ROOT} && php8.4 artisan view:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan cache:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan config:clear",
    "systemctl --user restart highest-queue-default.service",
]

for cmd in cmds:
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"CMD: {cmd} => {out}")

client.close()
print("[Deploy] Complete!")
