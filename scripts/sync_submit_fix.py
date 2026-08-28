import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

files = [
    ("e:/HIGESTO NEW1/higest/higest101/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php",
     "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php"),
    ("e:/HIGESTO NEW1/higest/higest101/packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php",
     "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php"),
]

for local, remote in files:
    with open(local, "rb") as f:
        sftp.putfo(f, remote)
    print(f"Synced {remote.split('/')[-1]}")

sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan optimize:clear")
print("Cleared cache.")

client.close()
