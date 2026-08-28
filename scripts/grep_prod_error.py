import remote_ssh_helper as r

client = r.get_ssh_client()

code, out, err = r.run_remote_cmd(client, "grep -n -C 5 'production.ERROR' /home/highest-ye/htdocs/highest-ye.store/storage/logs/laravel.log | tail -n 60")
print(f"PRODUCTION ERRORS:\n{out}")

client.close()
