import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

# Upload modified files
local_dto = "e:/HIGESTO NEW1/higest/higest101/app/Services/AliExpress/DTO/ValidatedAliExpressShippingAddress.php"
remote_dto = "/home/highest-ye/htdocs/highest-ye.store/app/Services/AliExpress/DTO/ValidatedAliExpressShippingAddress.php"

local_gw = "e:/HIGESTO NEW1\higest/higest101/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php"
remote_gw = "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php"

with open(local_dto, "rb") as f:
    sftp.putfo(f, remote_dto)
print("Uploaded ValidatedAliExpressShippingAddress.php")

with open(local_gw, "rb") as f:
    sftp.putfo(f, remote_gw)
print("Uploaded AliExpressOrderSubmissionGateway.php")

sftp.close()

# Clear cache and optimize
code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan optimize:clear")
print(f"Artisan clear:\n{out}")

client.close()
