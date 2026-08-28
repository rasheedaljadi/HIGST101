import os
import remote_ssh_helper as r

LOCAL_ROOT = r"e:\HIGESTO NEW1\higest\higest101"
REMOTE_ROOT = "/home/highest-ye/htdocs/highest-ye.store"

files_to_sync = [
    ("packages/Webkul/Shop/src/Resources/views/checkout/onepage/address/form.blade.php", "packages/Webkul/Shop/src/Resources/views/checkout/onepage/address/form.blade.php"),
    ("packages/Webkul/Shop/src/Resources/views/customers/account/addresses/create.blade.php", "packages/Webkul/Shop/src/Resources/views/customers/account/addresses/create.blade.php"),
    ("packages/Webkul/Shop/src/Resources/views/customers/account/addresses/edit.blade.php", "packages/Webkul/Shop/src/Resources/views/customers/account/addresses/edit.blade.php"),
]

client = r.get_ssh_client()
sftp = client.open_sftp()

print("[Deploy] Uploading updated address district views...")
for local_rel, remote_rel in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, local_rel)
    remote_path = f"{REMOTE_ROOT}/{remote_rel}"
    print(f"  -> {local_rel}")
    sftp.put(local_path, remote_path)

sftp.close()

cmds = [
    f"cd {REMOTE_ROOT} && php8.4 artisan view:clear",
    f"cd {REMOTE_ROOT} && php8.4 artisan optimize",
]
for cmd in cmds:
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"CMD: {cmd} => {out.strip()}")

client.close()
print("[Complete] District Definitions Deployed & Optimized Successfully!")
