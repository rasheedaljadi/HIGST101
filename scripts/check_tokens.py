import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    script_php = f"""<?php
$projDir = '{remote_base}';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$tokens = DB::table('aliexpress_tokens')->get(['id', 'account', 'account_id', 'seller_id', 'access_token_expires_at', 'created_at']);
echo json_encode($tokens, JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/check_tokens.php', 'w') as f:
        f.write(script_php)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/check_tokens.php && rm -f /tmp/check_tokens.php")
    print(out)
    client.close()

if __name__ == '__main__':
    main()
