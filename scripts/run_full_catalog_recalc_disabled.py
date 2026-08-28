import remote_ssh_helper as r

client = r.get_ssh_client()

print("[Recalculate] Starting full batch price recalculation for disabled shipping on production...")
code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan higest:pricing:recalculate --trigger=rule_change")
print("EXIT:", code)
print("OUT:\n" + out)
if err:
    print("ERR:\n" + err)

client.close()
