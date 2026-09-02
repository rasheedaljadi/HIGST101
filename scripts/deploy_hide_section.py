import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

f = "resources/views/aliexpress/keys.blade.php"
sftp.put(f"{local_base}/{f}", f"{remote_base}/{f}")
sftp.close()

# Clear and re-cache views
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan view:clear && php8.4 artisan view:cache")
print(f"View Cache: CODE {code}\n{out}")

client.close()
