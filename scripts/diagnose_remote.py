import remote_ssh_helper as r

client = r.get_ssh_client()
cmds = [
    "cd /home/highest-ye/htdocs/highest-ye.store && tail -n 50 storage/logs/laravel.log",
    "cd /home/highest-ye/htdocs/highest-ye.store && tail -n 50 storage/logs/aliexpress.log 2>/dev/null || true",
    "cd /home/highest-ye/htdocs/highest-ye.store && php artisan tinker --execute=\"echo json_encode(\App\Models\AliExpressSetting::first());\"",
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")
    if err:
        print(f"ERR:\n{err}")

client.close()
