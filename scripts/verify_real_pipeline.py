import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

cmd = """cd /home/highest-ye/htdocs/highest-ye.store && php artisan tinker --execute="
    \$svc = app(\Webkul\Sales\Services\Lifecycle\OrderLifecycleDashboardQueryService::class);
    \$summary = \$svc->getPipelineSummary();
    foreach (\$summary['stages'] as \$stg) {
        echo 'STAGE ' . \$stg['rank'] . ' (' . \$stg['label'] . '): count=' . \$stg['count'] . ' value=' . \$stg['value'] . ' orders_count=' . count(\$stg['orders']) . PHP_EOL;
        foreach (\$stg['orders'] as \$ord) {
            echo '   -> ORD: ' . \$ord['number'] . ' | ' . \$ord['status_label'] . ' | ' . \$ord['view_url'] . PHP_EOL;
        }
    }
"
"""
stdin, stdout, stderr = client.exec_command(cmd)
print("STDOUT:\n", stdout.read().decode('utf-8'))
client.close()
