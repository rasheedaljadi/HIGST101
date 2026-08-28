import remote_ssh_helper as r

client = r.get_ssh_client()
cmds = [
    """cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan tinker --execute="$first = \DB::table('jobs')->first(); $payload = json_decode($first->payload, true); echo 'Job displayName: ' . ($payload['displayName'] ?? 'N/A') . PHP_EOL; echo 'Available at: ' . date('Y-m-d H:i:s', $first->available_at) . PHP_EOL; echo 'Created at: ' . date('Y-m-d H:i:s', $first->created_at) . PHP_EOL;" """,
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")

client.close()
