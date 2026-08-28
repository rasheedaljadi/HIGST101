import remote_ssh_helper as r

client = r.get_ssh_client()
code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && rm -f sync_pint_gw.py")
print("Cleaned up sync_pint_gw.py")
client.close()
