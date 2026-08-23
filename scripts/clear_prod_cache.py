import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

stdin, stdout, stderr = client.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php artisan optimize:clear')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
