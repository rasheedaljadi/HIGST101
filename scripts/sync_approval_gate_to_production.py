import remote_ssh_helper as r

client = r.get_ssh_client()

files_to_sync = [
    (
        r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Gateways\AliExpressOrderSubmissionGateway.php",
        "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php"
    ),
    (
        r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Services\ProcurementSubmitService.php",
        "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php"
    ),
    (
        r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Services\ProcurementBatchService.php",
        "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Services/ProcurementBatchService.php"
    ),
]

sftp = client.open_sftp()
for local, remote in files_to_sync:
    print(f"Uploading {local} -> {remote}...")
    sftp.put(local, remote)
sftp.close()
print("All files uploaded successfully!")

# Clear cache on remote
code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php artisan optimize:clear")
print(f"Clear cache output:\n{out}")

client.close()
