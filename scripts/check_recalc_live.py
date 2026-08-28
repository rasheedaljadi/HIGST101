import remote_ssh_helper as r

client = r.get_ssh_client()
cmds = [
    "ps aux | grep -i recalculate | grep -v grep || echo 'Process completed!'",
    "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan tinker --execute=\"echo 'Queue worker status: ' . \DB::table('jobs')->where('queue', 'default')->count() . ' default jobs remaining.' . PHP_EOL;\"",
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")

client.close()
