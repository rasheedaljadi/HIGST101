import paramiko

hostname = "76.13.79.242"
username = "highest-ye"
password = "YoK2PBV1fo82yujX2tDq"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(hostname, username=username, password=password, timeout=15)

project_dir = "/home/highest-ye/htdocs/highest-ye.store"
code = '$t9 = \\Illuminate\\Support\\Facades\\DB::table("wallet_transactions")->where("id", 9)->update(["type" => "CREDIT_PROMOTION"]); $tx11 = \\Illuminate\\Support\\Facades\\DB::table("wallet_transactions")->where("id", 11)->first(); $tx13 = \\Illuminate\\Support\\Facades\\DB::table("wallet_transactions")->where("id", 13)->first(); if ($tx11 && $tx13) { $overCredit = (float)$tx11->amount + (float)$tx13->amount; $walletId = $tx11->wallet_id; \\Illuminate\\Support\\Facades\\DB::table("wallet_transactions")->whereIn("id", [11, 13])->delete(); echo "Deleted duplicate transactions 11 and 13\n"; $w = \\Illuminate\\Support\\Facades\\DB::table("wallet_accounts")->where("id", $walletId)->first(); if ($w) { \\Illuminate\\Support\\Facades\\DB::table("wallet_accounts")->where("id", $walletId)->update(["available_balance" => max(0, $w->available_balance - $overCredit), "total_balance" => max(0, $w->total_balance - $overCredit)]); echo "Adjusted balance\n"; } }'

artisan_cmd = f"cd {project_dir} && php artisan tinker --execute='{code}'"

stdin, stdout, stderr = client.exec_command(artisan_cmd)
out = stdout.read().decode("utf-8", errors="ignore")

print("=== STDOUT ===")
print(out)
client.close()
