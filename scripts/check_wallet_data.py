import paramiko

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=15)

sftp = client.open_sftp()

php_test = r'''<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$activeWallets = DB::table('wallet_accounts')->where('status', 'active');
$totalLiability = (float) (clone $activeWallets)->sum('total_balance');
$availableBalance = (float) (clone $activeWallets)->sum('available_balance');
$heldBalance = (float) (clone $activeWallets)->sum('held_balance');
$totalAccounts = DB::table('wallet_accounts')->count();
$activeAccounts = (clone $activeWallets)->count();

$pendingWithdrawalsQuery = DB::table('wallet_withdrawal_requests')->where('status', 'pending');
$pendingWithdrawals = (int) (clone $pendingWithdrawalsQuery)->count();
$pendingWithdrawalsAmount = (float) (clone $pendingWithdrawalsQuery)->sum('amount');

echo json_encode([
    'totalLiability' => $totalLiability,
    'availableBalance' => $availableBalance,
    'heldBalance' => $heldBalance,
    'totalAccounts' => $totalAccounts,
    'activeAccounts' => $activeAccounts,
    'pendingWithdrawals' => $pendingWithdrawals,
    'pendingWithdrawalsAmount' => $pendingWithdrawalsAmount,
]);
'''

with sftp.file(f"{APP_DIR}/tmp_test_wallet.php", "w") as f:
    f.write(php_test)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php tmp_test_wallet.php && rm tmp_test_wallet.php")
out = stdout.read().decode('utf-8', errors='replace').strip()
err = stderr.read().decode('utf-8', errors='replace').strip()

print("WALLET DATA:")
print(out)
if err:
    print("ERR:", err)

client.close()
