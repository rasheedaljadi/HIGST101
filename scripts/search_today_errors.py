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

remote_script = """
with open('storage/logs/laravel.log', 'r', errors='ignore') as f:
    text = f.read()

entries = text.split('[2026-')
print(f'Total log entries: {len(entries)}')
for entry in entries:
    if '2026-08-26' in entry and ('ERROR' in entry or 'Exception' in entry):
        lines = entry.strip().split('\\n')
        print('================ ERROR ENTRY ================')
        for l in lines[:20]:
            print(l)
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/find_errors.py", 'w') as f:
    f.write(remote_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && python3 find_errors.py && rm find_errors.py")
print(stdout.read().decode('utf-8', errors='replace'))

client.close()
