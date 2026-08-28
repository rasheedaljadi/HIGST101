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

$blade = file_get_contents('/home/highest-ye/htdocs/highest-ye.store/public/blade_cust.html');

// Compare the first <tr> of blade_cust.html
preg_match_all('/<tr>(.*?)<\/tr>/is', $blade, $matches);
echo "Total <tr> count in blade_cust.html: " . count($matches[0]) . "\\n";

foreach (array_slice($matches[0], 0, 5) as $i => $row) {
    echo "Row $i:\\n$row\\n-----------------\\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/compare_rows.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php compare_rows.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/compare_rows.php")
client.close()
