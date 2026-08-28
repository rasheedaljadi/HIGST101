import remote_ssh_helper as r

client = r.get_ssh_client()
code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan optimize")
print(f"OUT: {out}")
client.close()
