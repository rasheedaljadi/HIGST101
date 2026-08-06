import paramiko

hostname = "76.13.79.242"
username = "highest-ye"
password = "YoK2PBV1fo82yujX2tDq"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(hostname, username=username, password=password, timeout=15)

project_dir = "/home/highest-ye/htdocs/highest-ye.store"
code = '$order = \\Webkul\\Sales\\Models\\Order::find(240); if ($order) { echo "Order #240 Status: " . $order->status . PHP_EOL; var_dump($order->canCancel()); foreach ($order->items as $item) { var_dump($item->canCancel()); } }'

artisan_cmd = f"cd {project_dir} && php artisan tinker --execute='{code}'"

stdin, stdout, stderr = client.exec_command(artisan_cmd)
out = stdout.read().decode("utf-8", errors="ignore")

print("=== STDOUT ===")
print(out)
client.close()
