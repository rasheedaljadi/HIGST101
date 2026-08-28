import os
import remote_ssh_helper as r

LOCAL_ROOT = r"e:\HIGESTO NEW1\higest\higest101"
REMOTE_ROOT = "/home/highest-ye/htdocs/highest-ye.store"

files_to_sync = [
    ("app/Jobs/Pricing/RecalculateCatalogPricesJob.php", "app/Jobs/Pricing/RecalculateCatalogPricesJob.php"),
    ("app/Http/Controllers/AliExpress/AliExpressKeysController.php", "app/Http/Controllers/AliExpress/AliExpressKeysController.php"),
    ("packages/Webkul/Shop/src/Transformers/ProductPDPTransformer.php", "packages/Webkul/Shop/src/Transformers/ProductPDPTransformer.php"),
]

client = r.get_ssh_client()
sftp = client.open_sftp()

for local_rel, remote_rel in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, local_rel)
    remote_path = f"{REMOTE_ROOT}/{remote_rel}"
    print(f"[Deploy] Uploading {local_rel} -> {remote_path}")
    sftp.put(local_path, remote_path)

# Update systemd service to ensure 'pricing' is top priority
service_content = """[Unit]
Description=Highest Default and Priority Pricing Queue Worker
After=network.target

[Service]
Type=simple
WorkingDirectory=/home/highest-ye/htdocs/highest-ye.store
ExecStart=/usr/bin/php8.4 /home/highest-ye/htdocs/highest-ye.store/artisan queue:work database --queue=pricing,default,broadcastable --sleep=1 --tries=3 --timeout=900
Restart=always
RestartSec=5
StandardOutput=append:/home/highest-ye/htdocs/highest-ye.store/storage/logs/queue-default.log
StandardError=append:/home/highest-ye/htdocs/highest-ye.store/storage/logs/queue-default.log

[Install]
WantedBy=default.target
"""

with sftp.file("/home/highest-ye/.config/systemd/user/highest-queue-default.service", "w") as f:
    f.write(service_content)

sftp.close()

cmds = [
    "systemctl --user daemon-reload",
    "systemctl --user restart highest-queue-default.service",
    f"cd {REMOTE_ROOT} && php8.4 artisan view:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan cache:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan config:clear",
]

for cmd in cmds:
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"CMD: {cmd} => {out}")

client.close()
print("[Deploy] Complete!")
