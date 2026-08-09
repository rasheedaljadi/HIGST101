import paramiko

hostname = "76.13.79.242"
username = "highest-ye"
password = "YoK2PBV1fo82yujX2tDq"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

print(f"Connecting to {username}@{hostname}...")
client.connect(hostname, username=username, password=password, timeout=15)

project_dir = "/home/highest-ye/htdocs/highest-ye.store"
script = r"""
$p = app('Webkul\Product\Repositories\ProductRepository')->find(283);
if ($p) {
    echo "ID: " . $p->id . "\n";
    echo "URL_KEY: " . $p->url_key . "\n";
}
"""

cmd = f"cd '{project_dir}' && php artisan tinker --execute=\"{script}\""

stdin, stdout, stderr = client.exec_command(cmd)
out = stdout.read().decode("utf-8", errors="ignore")
err = stderr.read().decode("utf-8", errors="ignore")

print("=== OUT ===")
print(out)
print("=== ERR ===")
print(err)

client.close()
