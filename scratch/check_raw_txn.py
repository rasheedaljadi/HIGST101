import paramiko

hostname = "76.13.79.242"
username = "highest-ye"
password = "YoK2PBV1fo82yujX2tDq"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(hostname, username=username, password=password, timeout=15)

project_dir = "/home/highest-ye/htdocs/highest-ye.store"
code = '$t = \\Illuminate\\Support\\Facades\\DB::table("wallet_transactions")->where("id", 9)->first(); print_r($t);'

artisan_cmd = f"cd {project_dir} && php artisan tinker --execute='{code}'"

stdin, stdout, stderr = client.exec_command(artisan_cmd)
out = stdout.read().decode("utf-8", errors="ignore")

print("=== STDOUT ===")
print(out)
client.close()
