import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("e:/HIGESTO NEW1/higest/higest101/app/Services/AliExpress/Shipping/AliExpressShippingAddressValidator.php",
     "/home/highest-ye/htdocs/highest-ye.store/app/Services/AliExpress/Shipping/AliExpressShippingAddressValidator.php"),
    ("e:/HIGESTO NEW1/higest/higest101/app/Services/AliExpress/DTO/ValidatedAliExpressShippingAddress.php",
     "/home/highest-ye/htdocs/highest-ye.store/app/Services/AliExpress/DTO/ValidatedAliExpressShippingAddress.php"),
    ("e:/HIGESTO NEW1/higest/higest101/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php",
     "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php"),
]

for local, remote in files_to_sync:
    with open(local, "rb") as f:
        sftp.putfo(f, remote)
    print(f"Synced {remote.split('/')[-1]}")

sftp.close()

# Clear cache
code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan optimize:clear")
print("Cache cleared.")

client.close()
