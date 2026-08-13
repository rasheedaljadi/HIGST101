<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['wallet_promotion_grants', 'wallet_promo_debts', 'wallet_promotion_outbox'] as $t) {
    $res = DB::select("SHOW CREATE TABLE `{$t}`");
    echo "=== {$t} ===\n" . $res[0]->{'Create Table'} . "\n\n";
}
