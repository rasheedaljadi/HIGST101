import remote_ssh_helper as r

client = r.get_ssh_client()

service_content = """[Unit]
Description=Highest Default Queue Worker
After=network.target

[Service]
Type=simple
WorkingDirectory=/home/highest-ye/htdocs/highest-ye.store
ExecStart=/usr/bin/php8.4 /home/highest-ye/htdocs/highest-ye.store/artisan queue:work database --queue=default,broadcastable --sleep=2 --tries=3 --timeout=900
Restart=always
RestartSec=5
StandardOutput=append:/home/highest-ye/htdocs/highest-ye.store/storage/logs/queue-default.log
StandardError=append:/home/highest-ye/htdocs/highest-ye.store/storage/logs/queue-default.log

[Install]
WantedBy=default.target
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/.config/systemd/user/highest-queue-default.service", "w") as f:
    f.write(service_content)
sftp.close()

cmds = [
    "systemctl --user daemon-reload",
    "systemctl --user enable highest-queue-default.service",
    "systemctl --user restart highest-queue-default.service",
    "systemctl --user is-active highest-queue-default.service",
    """crontab -l | grep -v 'highest-queue-default.service' > /tmp/mycron && echo '* * * * * systemctl --user is-active highest-queue-default.service > /dev/null 2>&1 || systemctl --user start highest-queue-default.service > /dev/null 2>&1' >> /tmp/mycron && crontab /tmp/mycron && rm /tmp/mycron""",
]

for cmd in cmds:
    print(f"\n=== CMD: {cmd} ===")
    code, out, err = r.run_remote_cmd(client, cmd)
    print(f"OUT:\n{out}")
    if err:
        print(f"ERR:\n{err}")

client.close()
