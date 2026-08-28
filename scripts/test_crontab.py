import remote_ssh_helper as r

client = r.get_ssh_client()
cmds = [
    "crontab -l 2>/dev/null || true",
    "systemctl --user list-units --type=service 2>/dev/null || true",
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")
    if err:
        print(f"ERR:\n{err}")

client.close()
