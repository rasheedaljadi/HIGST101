import remote_ssh_helper as r

client = r.get_ssh_client()
cmds = [
    "ps aux | grep -i recalculate | grep -v grep || true",
    "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan tinker --execute=\"echo 'Total Offers: ' . \App\Models\HigestSourceOffer::count() . PHP_EOL; echo 'Updated recently: ' . \App\Models\HigestSourceOffer::where('updated_at', '>=', now()->subMinutes(20))->count() . PHP_EOL;\"",
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")
    if err:
        print(f"ERR:\n{err}")

client.close()
