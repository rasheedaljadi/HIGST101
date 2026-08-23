import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    php = """<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jobs = Illuminate\Support\Facades\DB::table('jobs')->get(['id', 'queue', 'attempts', 'reserved_at', 'available_at']);
echo json_encode($jobs, JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/inspect_jobs.php', 'w') as f:
        f.write(php)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/inspect_jobs.php && rm -f /tmp/inspect_jobs.php")
    print(out)
    client.close()

if __name__ == '__main__':
    main()
