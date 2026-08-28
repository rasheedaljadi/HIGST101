import sys
import paramiko
from pypdf import PdfReader

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)

gen_script = """<?php
require '/home/highest-ye/htdocs/highest-ye.store/vendor/autoload.php';
$app = require_once '/home/highest-ye/htdocs/highest-ye.store/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

app()->setLocale('ar');
$controller = app()->make(\\Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController::class);
$response = $controller->exportProducts(new \\Illuminate\\Http\\Request(['format' => 'pdf']));

file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/check_final.pdf', $response->getContent());
echo "Saved check_final.pdf (" . filesize('/home/highest-ye/htdocs/highest-ye.store/public/check_final.pdf') . " bytes)\\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/gen_check.php", 'w') as f:
    f.write(gen_script)

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php gen_check.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

sftp.get(f"{APP_DIR}/public/check_final.pdf", "check_final_local.pdf")
sftp.remove(f"{APP_DIR}/gen_check.php")
sftp.remove(f"{APP_DIR}/public/check_final.pdf")
sftp.close()
client.close()

reader = PdfReader("check_final_local.pdf")
print("Total pages:", len(reader.pages))
print("Page 1 Text:")
print(reader.pages[0].extract_text()[:600])
