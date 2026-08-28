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

// Remove .data-table thead { display: table-header-group; }
$clean = str_replace('.data-table thead {', '/* .data-table thead {', $blade);
$clean = str_replace('display: table-header-group;', '*/', $clean);

$m = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
$m->SetDirectionality('rtl');
$m->autoScriptToLang = true;
$m->autoLangToFont = true;
$m->WriteHTML($clean);

echo "Page count after removing 'display: table-header-group': " . $m->page . "\\n";
file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/fixed_customers_report.pdf', $m->Output('', 'S'));
echo "Saved fixed_customers_report.pdf to public\\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_header_group.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_header_group.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_header_group.php")
client.close()
