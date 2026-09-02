import remote_ssh_helper as r

client = r.get_ssh_client()

remote_base = "/home/highest-ye/htdocs/highest-ye.store"

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan cache:clear && php8.4 artisan view:cache && php8.4 artisan route:cache")
print(f"Cache output:\n{out}")

client.close()
