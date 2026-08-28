import remote_ssh_helper as r

client = r.get_ssh_client()
cmds = [
    "crontab -l || true",
    "cat /etc/supervisor/conf.d/*.conf 2>/dev/null || true",
    "systemctl list-units --type=service | grep -i queue || true",
    "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan tinker --execute=\"echo json_encode(\DB::table('jobs')->select('queue', \DB::raw('count(*) as count'))->groupBy('queue')->get(), JSON_PRETTY_PRINT) . PHP_EOL;\"",
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")

client.close()
