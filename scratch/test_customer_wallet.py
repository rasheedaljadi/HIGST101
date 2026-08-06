import paramiko

hostname = "76.13.79.242"
username = "highest-ye"
password = "YoK2PBV1fo82yujX2tDq"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(hostname, username=username, password=password, timeout=15)

project_dir = "/home/highest-ye/htdocs/highest-ye.store"
test_cmd = f"cd '{project_dir}' && php artisan tinker --execute=\"print_r(\\Webkul\\Customer\\Models\\Customer::all()->pluck('email', 'id')->toArray()); print_r(\\Webkul\\Wallet\\Models\\WalletAccount::all()->toArray());\""

stdin, stdout, stderr = client.exec_command(test_cmd)
out = stdout.read().decode("utf-8", errors="ignore")
err = stderr.read().decode("utf-8", errors="ignore")

print("=== STDOUT ===")
print(out)
print("=== STDERR ===")
print(err)

client.close()
