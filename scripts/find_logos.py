import sys
import paramiko

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)

test_script = """<?php
require '/home/highest-ye/htdocs/highest-ye.store/vendor/autoload.php';
$app = require_once '/home/highest-ye/htdocs/highest-ye.store/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\Storage;

$logoConfig = core()->getConfigData('general.design.admin_logo.logo_image');
echo "Logo config: " . var_export($logoConfig, true) . "\\n";

if ($logoConfig) {
    echo "Storage path: " . Storage::path($logoConfig) . "\\n";
    echo "Storage exists: " . (Storage::exists($logoConfig) ? 'YES' : 'NO') . "\\n";
}

$candidates = [
    public_path('images/logo.svg'),
    public_path('images/logo.png'),
    public_path('themes/shop/default/build/assets/logo.png'),
    public_path('themes/admin/default/build/assets/logo.png'),
    public_path('themes/admin/default/build/assets/logo.svg'),
    public_path('storage/logo.png'),
];

foreach ($candidates as $c) {
    echo "$c => " . (file_exists($c) ? 'EXISTS (' . filesize($c) . ' bytes)' : 'NOT FOUND') . "\\n";
}

// Find any png/jpg logo in public or storage
exec('find /home/highest-ye/htdocs/highest-ye.store/public -name "*logo*"', $foundPublic);
echo "Found in public: " . json_encode($foundPublic, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\\n";

exec('find /home/highest-ye/htdocs/highest-ye.store/storage -name "*logo*"', $foundStorage);
echo "Found in storage: " . json_encode($foundStorage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/find_logos.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php find_logos.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/find_logos.php")
client.close()
