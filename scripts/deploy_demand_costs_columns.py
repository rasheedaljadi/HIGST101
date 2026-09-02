import os
import remote_ssh_helper as r

client = r.get_ssh_client()

local_lang_dir = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"
remote_lang_dir = "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Resources/lang"

sftp = client.open_sftp()

# 1. Upload ProcurementDemandDataGrid.php
local_datagrid = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\DataGrids\ProcurementDemandDataGrid.php"
remote_datagrid = "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/DataGrids/ProcurementDemandDataGrid.php"
print(f"Uploading {local_datagrid} -> {remote_datagrid}...")
sftp.put(local_datagrid, remote_datagrid)

# 2. Upload all 21 locale files
for loc in os.listdir(local_lang_dir):
    local_file = os.path.join(local_lang_dir, loc, "app.php")
    remote_file = f"{remote_lang_dir}/{loc}/app.php"
    if os.path.isfile(local_file):
        print(f"Uploading {loc}/app.php -> {remote_file}...")
        sftp.put(local_file, remote_file)

sftp.close()
print("All files synced successfully!")

# Clear cache on remote
code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php artisan optimize:clear")
print(f"Clear cache output:\n{out}")

client.close()
