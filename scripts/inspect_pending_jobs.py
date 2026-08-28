import remote_ssh_helper as r

client = r.get_ssh_client()
cmds = [
    "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan tinker --execute=\"echo 'Oldest job ID: ' . \DB::table('jobs')->min('id') . ', Newest: ' . \DB::table('jobs')->max('id') . PHP_EOL; echo json_encode(\DB::table('jobs')->take(10)->pluck('displayName'), JSON_PRETTY_PRINT) . PHP_EOL;\"",
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")

client.close()
