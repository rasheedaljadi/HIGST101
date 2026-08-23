import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    cmd = f"""cd {remote_base} && php -r '
    require "vendor/autoload.php";
    $app = require_once "bootstrap/app.php";
    $kernel = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);
    $kernel->bootstrap();
    $src = \\Illuminate\\Support\\Facades\\DB::table("inventory_sources")->where("code", "default")->first();
    $postcode = (string)($src->postcode ?? "");
    echo json_encode([
        "code" => $src->code,
        "country" => $src->country,
        "postcode_len" => strlen($postcode),
        "postcode_masked" => substr($postcode, 0, 2) . "****" . substr($postcode, -2),
        "prefix_letters" => substr($postcode, 0, 4),
        "suffix_digits" => substr($postcode, 4),
        "matches_pattern" => (bool)preg_match("/^[A-Z]{4}[0-9]{4}$/", $postcode)
    ]);
    '"""
    code, out, err = run_remote_cmd(client, cmd)
    print(out)
    client.close()

if __name__ == '__main__':
    main()
