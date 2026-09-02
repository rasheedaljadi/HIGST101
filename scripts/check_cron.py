import remote_ssh_helper as r

client = r.get_ssh_client()

code, out, err = r.run_remote_cmd(client, "crontab -l")
print(f"Crontab:\n{out}")

client.close()
