import remote_ssh_helper as r

client = r.get_ssh_client()
cmds = [
    "ls -la ~/.config/systemd/user/ || true",
    "cat ~/.config/systemd/user/*.service 2>/dev/null || true",
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")

client.close()
