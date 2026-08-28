import remote_ssh_helper as r

client = r.get_ssh_client()
cmds = [
    "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan tinker --execute=\"echo 'Queue driver: ' . config('queue.default') . PHP_EOL; echo 'Pending jobs count: ' . \DB::table('jobs')->count() . PHP_EOL; echo 'Failed jobs count: ' . \DB::table('failed_jobs')->count() . PHP_EOL;\"",
    "ps aux | grep -i queue | grep -v grep || echo 'No queue worker running!'",
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")

client.close()
