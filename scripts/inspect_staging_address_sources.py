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
    
    $invSource = (array) \\Illuminate\\Support\\Facades\\DB::table("inventory_sources")->where("code", "default")->first();
    $allInvSources = \\Illuminate\\Support\\Facades\\DB::table("inventory_sources")->get(["code", "name", "country", "city", "state", "postcode", "street"])->toArray();
    $aeSettings = (array) \\Illuminate\\Support\\Facades\\DB::table("aliexpress_settings")->first();
    
    // Mask sensitive strings
    $maskedInv = [];
    foreach ($invSource as $k => $v) {
        if (is_string($v) && strlen($v) > 4) {
            $maskedInv[$k] = substr($v, 0, 2) . "***" . substr($v, -2) . " (len: " . strlen($v) . ")";
        } else {
            $maskedInv[$k] = $v;
        }
    }
    
    $maskedSettings = [];
    foreach ($aeSettings as $k => $v) {
        if (is_string($v) && strlen($v) > 4) {
            $maskedSettings[$k] = substr($v, 0, 2) . "***" . substr($v, -2) . " (len: " . strlen($v) . ")";
        } else {
            $maskedSettings[$k] = $v;
        }
    }
    
    echo json_encode([
        "inv_source_columns" => array_keys($invSource),
        "default_inv_source_masked" => $maskedInv,
        "all_sources_summary" => $allInvSources,
        "ae_settings_columns" => array_keys($aeSettings),
        "ae_settings_masked" => $maskedSettings
    ], JSON_PRETTY_PRINT);
    '"""
    code, out, err = run_remote_cmd(client, cmd)
    print("--- Staging Address Sources Audit ---")
    print(out)
    client.close()

if __name__ == '__main__':
    main()
