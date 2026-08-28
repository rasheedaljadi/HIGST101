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

// 1. Strip 4-byte UTF8 emojis like 👥, 📊, etc.
$noEmojis = preg_replace('/[\\x{10000}-\\x{10FFFF}]/u', '', $blade);

$m = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
$m->SetDirectionality('rtl');
$m->autoScriptToLang = true;
$m->autoLangToFont = true;
$m->WriteHTML($noEmojis);

echo "Pages WITHOUT Emojis: " . $m->page . "\\n";

// 2. What if we also remove em-dash '—' or replace with '-'
$noDash = str_replace('—', '-', $noEmojis);
$m2 = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
$m2->SetDirectionality('rtl');
$m2->autoScriptToLang = true;
$m2->autoLangToFont = true;
$m2->WriteHTML($noDash);

echo "Pages WITHOUT Emojis and without em-dash: " . $m2->page . "\\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_emojis.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_emojis.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_emojis.php")
client.close()
