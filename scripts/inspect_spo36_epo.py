import json
import sys
from remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    cmd = """cd /home/highest-ye/htdocs/highest-ye.store && php -r '
    require "vendor/autoload.php";
    $app = require_once "bootstrap/app.php";
    $k = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);
    $k->bootstrap();
    $epo = \\Illuminate\\Support\\Facades\\DB::table("external_platform_orders")->where("supplier_purchase_order_id", 36)->first();
    echo json_encode($epo, JSON_PRETTY_PRINT);
    '"""
    code, out, err = run_remote_cmd(client, cmd)
    print("--- EPO for SPO #36 ---")
    print(out)
    client.close()

if __name__ == '__main__':
    main()
