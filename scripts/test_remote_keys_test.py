import remote_ssh_helper as r

client = r.get_ssh_client()
cmds = [
    "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan test --filter=AliExpressKeysControllerTest",
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")
    if err:
        print(f"ERR:\n{err}")

client.close()
