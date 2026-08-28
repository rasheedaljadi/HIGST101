import remote_ssh_helper as r

client = r.get_ssh_client()

code, out, err = r.run_remote_cmd(client, "tail -n 120 /home/highest-ye/htdocs/highest-ye.store/storage/logs/laravel.log")
print(f"LAST LOGS:\n{out}")

client.close()
