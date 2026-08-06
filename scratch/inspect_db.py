import paramiko

hostname = "76.13.79.242"
username = "highest-ye"
password = "YoK2PBV1fo82yujX2tDq"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(hostname, username=username, password=password, timeout=15)

project_dir = "/home/highest-ye/htdocs/highest-ye.store"
inspect_cmd = f"cd '{project_dir}' && php artisan tinker --execute=\"\\Webkul\\Wallet\\Models\\WalletWithdrawalRequest::all()->each(fn(\$w) => print_r([\$w->id, \$w->getRawOriginal('bank_details'), \$w->bank_details]));\""

stdin, stdout, stderr = client.exec_command(inspect_cmd)
out = stdout.read().decode("utf-8", errors="ignore")
err = stderr.read().decode("utf-8", errors="ignore")

print("=== STDOUT ===")
print(out)
print("=== STDERR ===")
print(err)

client.close()
