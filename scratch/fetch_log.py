import paramiko

hostname = "76.13.79.242"
username = "highest-ye"
password = "YoK2PBV1fo82yujX2tDq"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(hostname, username=username, password=password, timeout=15)

project_dir = "/home/highest-ye/htdocs/highest-ye.store"
fetch_cmd = f"cd '{project_dir}' && tail -n 200 storage/logs/laravel.log | grep -B 2 -A 10 '\[2026-'"

stdin, stdout, stderr = client.exec_command(fetch_cmd)
out = stdout.read().decode("utf-8", errors="ignore")
err = stderr.read().decode("utf-8", errors="ignore")

print("=== STDOUT ===")
print(out)
print("=== STDERR ===")
print(err)

client.close()
