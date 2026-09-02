import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

local_path = r"e:\HIGESTO NEW1\higest\higest101\app\Services\AliExpress\AliExpressProductImporter.php"
remote_path = "/home/highest-ye/htdocs/highest-ye.store/app/Services/AliExpress/AliExpressProductImporter.php"

print(f"Uploading {local_path} -> {remote_path}")
sftp.put(local_path, remote_path)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan cache:clear")
print(f"Cache clear: CODE {code} | {out}")

client.close()
print("AliExpressProductImporter synced to production successfully!")
