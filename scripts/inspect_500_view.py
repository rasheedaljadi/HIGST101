import paramiko

hostname = '76.13.79.242'
username = 'highest-ye'
password = 'YoK2PBV1fo82yujX2tDq'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(hostname, username=username, password=password)

cmd = "sed -n '280,300p' /home/highest-ye/htdocs/highest-ye.store/storage/framework/views/7e2febfe6e9e20d915abd7b5f24bc01f.php"
stdin, stdout, stderr = ssh.exec_command(cmd)
print("=== COMPILED BLADE LINES 280-300 ===")
lines = stdout.read().decode('utf-8').splitlines()
for idx, line in enumerate(lines, start=280):
    print(f"{idx}: {line}")

ssh.close()
