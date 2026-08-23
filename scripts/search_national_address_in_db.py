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
    
    // Check if any column in inventory_sources or addresses contains an 8-char national address pattern
    $sources = \\Illuminate\\Support\\Facades\\DB::table("inventory_sources")->get()->toArray();
    $matchedSources = [];
    foreach ($sources as $s) {
        $sArr = (array) $s;
        foreach ($sArr as $col => $val) {
            if (is_string($val) && preg_match("/^[A-Za-z]{4}[0-9]{4}$/", trim($val))) {
                $matchedSources[] = ["id" => $sArr["id"], "code" => $sArr["code"], "col" => $col, "val_masked" => substr($val, 0, 2) . "****" . substr($val, -2)];
            }
        }
    }
    
    // Check addresses table
    $addrMatches = [];
    $addresses = \\Illuminate\\Support\\Facades\\DB::table("addresses")->get()->toArray();
    foreach ($addresses as $a) {
        $aArr = (array) $a;
        foreach ($aArr as $col => $val) {
            if (is_string($val) && preg_match("/^[A-Za-z]{4}[0-9]{4}$/", trim($val))) {
                $addrMatches[] = ["id" => $aArr["id"], "col" => $col, "val_masked" => substr($val, 0, 2) . "****" . substr($val, -2)];
            }
        }
    }
    
    echo json_encode([
        "matched_inventory_sources" => $matchedSources,
        "matched_addresses" => $addrMatches
    ], JSON_PRETTY_PRINT);
    '"""
    code, out, err = run_remote_cmd(client, cmd)
    print("--- National Address Search in DB ---")
    print(out)
    client.close()

if __name__ == '__main__':
    main()
