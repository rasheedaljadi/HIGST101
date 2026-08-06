import paramiko

hostname = "76.13.79.242"
username = "highest-ye"
password = "YoK2PBV1fo82yujX2tDq"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(hostname, username=username, password=password, timeout=15)

project_dir = "/home/highest-ye/htdocs/highest-ye.store"
artisan_cmd = f"cd {project_dir} && php artisan tinker --execute='var_dump(\\Webkul\\Sales\\Models\\OrderTransaction::select([\"id\", \"order_id\", \"invoice_id\", \"payment_method\", \"amount\"])->get()->toArray());'"

stdin, stdout, stderr = client.exec_command(artisan_cmd)
out = stdout.read().decode("utf-8", errors="ignore")
err = stderr.read().decode("utf-8", errors="ignore")

print("=== STDOUT ===")
print(out)
client.close()
