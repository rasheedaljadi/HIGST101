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
client.connect(HOST, username=USER, password=PASS, timeout=15)

stdin, stdout, stderr = client.exec_command(f"ls -la {APP_DIR}/public/*.hot")
print("Hot files on production:")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

stdin, stdout, stderr = client.exec_command(f"find {APP_DIR}/public -name '*.hot'")
print("Found hot files:")
print(stdout.read().decode('utf-8', errors='replace'))

client.close()
