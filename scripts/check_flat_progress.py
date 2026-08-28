import remote_ssh_helper as r

client = r.get_ssh_client()
cmds = [
    "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan tinker --execute=\"echo 'Recently updated flat products: ' . \DB::table('product_flat')->where('updated_at', '>=', now()->subMinutes(20))->count() . PHP_EOL;\"",
    "cd /home/highest-ye/htdocs/highest-ye.store && tail -n 20 storage/logs/aliexpress.log 2>/dev/null || true",
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")
    if err:
        print(f"ERR:\n{err}")

client.close()
