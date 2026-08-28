import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
$logFile = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recent = array_slice($lines, -80);
    echo implode("", $recent);
} else {
    echo "No log file found.";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/read_error_log.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 read_error_log.php && rm read_error_log.php")
print(f"OUTPUT:\n{out}")

client.close()
