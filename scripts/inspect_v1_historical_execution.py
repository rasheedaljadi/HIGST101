import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    
    php_code = r"""<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$pos = DB::table('purchase_orders')->get();
$tokens = DB::table('aliexpress_tokens')->get();
$settings = App\Models\AliExpressSetting::first();

$data = [
    'v1_purchase_orders' => $pos->map(function($p) {
        return [
            'id' => $p->id,
            'order_id' => $p->order_id ?? null,
            'internal_reference' => $p->internal_reference ?? null,
            'purchase_order_number' => $p->purchase_order_number ?? null,
            'state' => $p->state ?? null,
            'created_at' => (string) $p->created_at,
        ];
    }),
    'tokens_count' => $tokens->count(),
    'tokens' => $tokens->map(function($t) {
        return [
            'id' => $t->id,
            'user_nick' => $t->user_nick ?? null,
            'account' => $t->account ?? null,
            'has_access_token' => !empty($t->access_token),
            'expires_at' => (string) $t->access_token_expires_at,
            'is_valid' => strtotime((string) $t->access_token_expires_at) > time(),
        ];
    }),
    'app_key_env' => !empty(env('ALIEXPRESS_APP_KEY')),
];

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
"""
    
    sftp = client.open_sftp()
    with sftp.file('/tmp/audit_script.php', 'w') as f:
        f.write(php_code)
    sftp.close()
    
    cmd = "cd /home/highest-ye/htdocs/highest-ye.store && php /tmp/audit_script.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(out)
    if err:
        print("ERR:", err)
    client.close()

if __name__ == '__main__':
    main()
