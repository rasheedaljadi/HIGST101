import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    
    php = """<?php
$projDir = '/home/highest-ye/htdocs/highest-ye.store';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$fj = Illuminate\Support\Facades\DB::table('failed_jobs')->latest('id')->first();
if ($fj) {
    echo "FAILED JOB EXCEPTION:\n" . $fj->exception . "\n";
} else {
    echo "NO FAILED JOBS\n";
}
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/read_fj.php', 'w') as f:
        f.write(php)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/read_fj.php && rm -f /tmp/read_fj.php")
    print(out)
    client.close()

if __name__ == '__main__':
    main()
