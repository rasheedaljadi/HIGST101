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

client.exec_command(f"rm -f {APP_DIR}/public/*.pdf {APP_DIR}/public/*.html {APP_DIR}/*.php.bak {APP_DIR}/test_*.php {APP_DIR}/verify_*.php {APP_DIR}/inspect_*.php {APP_DIR}/debug_*.php {APP_DIR}/diff_*.php {APP_DIR}/pinpoint.php")
print("Cleaned up temporary diagnostic files from production.")
client.close()
