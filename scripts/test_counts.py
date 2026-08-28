import remote_ssh_helper as r

client = r.get_ssh_client()
cmds = [
    "cd /home/highest-ye/htdocs/highest-ye.store && php artisan tinker --execute=\"echo 'Offers count: ' . \App\Models\HigestSourceOffer::count() . PHP_EOL; echo 'Products count: ' . \Webkul\Product\Models\Product::count() . PHP_EOL; echo 'Imports count: ' . \App\Models\AliExpressProductImport::count() . PHP_EOL;\"",
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")
    if err:
        print(f"ERR:\n{err}")

client.close()
