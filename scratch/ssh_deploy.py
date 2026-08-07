import paramiko

hostname = "76.13.79.242"
username = "highest-ye"
password = "YoK2PBV1fo82yujX2tDq"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

print(f"Connecting to {username}@{hostname}...")
client.connect(hostname, username=username, password=password, timeout=15)
print("Connected successfully!")

project_dir = "/home/highest-ye/htdocs/highest-ye.store"
print(f"Target directory: {project_dir}")

deploy_cmd = f"cd '{project_dir}' && git fetch origin main && git reset --hard origin/main && composer dump-autoload --optimize && php artisan migrate --force && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache"

print(f"Executing deployment command on production server...")
stdin, stdout, stderr = client.exec_command(deploy_cmd)

out = stdout.read().decode("utf-8", errors="ignore")
err = stderr.read().decode("utf-8", errors="ignore")

print("=== STDOUT ===")
print(out)
print("=== STDERR ===")
print(err)

client.close()
