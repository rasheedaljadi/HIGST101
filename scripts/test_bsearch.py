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

// Let's test binary search of lines to see exactly WHICH line causes it to jump from 1 to 28 pages!
$lines = explode("\\n", $blade);
echo "Total lines: " . count($lines) . "\\n";

for ($n = 10; $n <= count($lines); $n += 10) {
    $sub = implode("\\n", array_slice($lines, 0, $n));
    if (!str_contains($sub, '</body>')) {
        $sub .= '</tbody></table></body></html>';
    }
    $m = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
    @$m->WriteHTML($sub);
    echo "First $n lines => {$m->page} pages\\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_bsearch.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_bsearch.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_bsearch.php")
client.close()
