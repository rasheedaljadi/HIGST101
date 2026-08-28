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
$reflector = new ReflectionClass(\\ArPHP\\I18N\\Arabic::class);
echo "File: " . $reflector->getFileName() . "\\n";

$lines = file($reflector->getFileName());
for ($i = 2385; $i <= 2415; $i++) {
    if (isset($lines[$i])) {
        echo ($i+1) . ": " . $lines[$i];
    }
}
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/inspect_ar_code.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php inspect_ar_code.php")
print(stdout.read().decode('utf-8', errors='replace'))
client.exec_command(f"rm {APP_DIR}/inspect_ar_code.php")
client.close()
