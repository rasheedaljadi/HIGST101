import paramiko

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=15)

print("=== CRONTAB ===")
stdin, stdout, stderr = client.exec_command("crontab -l")
print(stdout.read().decode('utf-8', errors='replace').strip())

print("\n=== SCHEDULE LIST ===")
stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php artisan schedule:list")
print(stdout.read().decode('utf-8', errors='replace').strip())

print("\n=== SEARCHING ALIEXPRESS / LARAVEL LOGS FOR CODE 604 ===")
stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && grep -C 5 -n '604' storage/logs/*.log 2>/dev/null | tail -n 40")
print(stdout.read().decode('utf-8', errors='replace').strip())

print("\n=== SEARCHING ALIEXPRESS / LARAVEL LOGS FOR 'All SKU Unsaleable' ===")
stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && grep -C 5 -n 'Unsaleable' storage/logs/*.log 2>/dev/null | tail -n 40")
print(stdout.read().decode('utf-8', errors='replace').strip())

client.close()
