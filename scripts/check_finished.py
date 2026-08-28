import remote_ssh_helper as r

client = r.get_ssh_client()
cmds = [
    "ps aux | grep -i recalculate | grep -v grep || echo 'Process finished!'",
    "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan cache:clear",
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")

client.close()
