<?php

namespace App\Console\Commands;

use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Probes the AliExpress dropshipping APIs related to SHIPPING (freight options,
 * tracking) and RETURNS/REFUNDS, using the stored token, and reports which the
 * current app key can actually call plus the shape of any successful response.
 *
 * Mirrors aliexpress:check-permissions / :explore-attributes. Read-only: the
 * order/return probes use minimal params only to learn whether the method is
 * permitted — they are not expected to create anything.
 *
 * Usage:
 *   php artisan aliexpress:explore-shipping --product-id=1005007021015396
 *   php artisan aliexpress:explore-shipping --product-id=... --dump
 */
class AliExpressExploreShipping extends Command
{
    protected $signature = 'aliexpress:explore-shipping
        {--product-id= : AliExpress product id used for freight/shipping probes}
        {--country=SA : ship-to country code}
        {--currency=USD : currency for shipping quotes}
        {--dump : Save successful raw JSON responses under storage/app/aliexpress/}';

    protected $description = 'Probe AliExpress shipping (freight/tracking) and return/refund APIs available to the current key';

    public function handle(AliExpressOAuthService $oauth, AliExpressApiClient $client): int
    {
        $token = $oauth->latestToken();

        if ($token === null) {
            $this->error('No AliExpress token found. Authorize at /aliexpress/connect first.');

            return self::FAILURE;
        }

        if (! $token->isAccessTokenValid()) {
            $this->warn('Stored access token appears expired and could not be refreshed. Re-authorize.');
        }

        $accessToken = $token->access_token;
        $productId = (string) ($this->option('product-id') ?: '1005007021015396');
        $country = (string) $this->option('country');
        $currency = (string) $this->option('currency');

        $probes = [
            // ── Shipping / freight ──
            'Freight (shipping options & cost)' => [
                'method' => 'aliexpress.ds.freight.query',
                'params' => [
                    'queryDeliveryReq' => [
                        'productId' => $productId,
                        'shipToCountry' => $country,
                        'quantity' => 1,
                        'currency' => $currency,
                        'language' => 'en_US',
                        'locale' => 'en_US',
                    ],
                ],
                'group' => 'shipping',
            ],
            'Shipping info (alt freight)' => [
                'method' => 'aliexpress.logistics.buyer.freight.calculate',
                'params' => [
                    'productId' => $productId,
                    'count' => 1,
                    'country' => $country,
                    'sendGoodsCountry' => 'CN',
                ],
                'group' => 'shipping',
            ],
            'Tracking (logistics info for an order)' => [
                'method' => 'aliexpress.ds.order.tracking.get',
                'params' => [
                    'ae_order_id' => '0',
                    'language' => 'en_US',
                ],
                'group' => 'shipping',
            ],
            'Logistics tracking (alt)' => [
                'method' => 'aliexpress.logistics.ds.trackinginfo.query',
                'params' => [
                    'logistics_no' => '0',
                    'origin' => 'ESCROW',
                    'out_ref' => '0',
                    'service_name' => 'GLOBAL_EUB',
                ],
                'group' => 'shipping',
            ],

            // ── Returns / refunds ──
            'Order detail (status for returns)' => [
                'method' => 'aliexpress.ds.order.get',
                'params' => [
                    'order_id' => '0',
                ],
                'group' => 'returns',
            ],
            'Issue / dispute create (refund)' => [
                'method' => 'aliexpress.ds.issue.create',
                'params' => [
                    'param0' => '0',
                ],
                'group' => 'returns',
            ],
            'Refund / reverse order' => [
                'method' => 'aliexpress.ds.refund.order.create',
                'params' => [
                    'param0' => '0',
                ],
                'group' => 'returns',
            ],
            'Order receipt confirm' => [
                'method' => 'aliexpress.trade.ds.order.tracking.get',
                'params' => [
                    'single_order_query' => ['order_id' => '0'],
                ],
                'group' => 'returns',
            ],
        ];

        $this->info("Probing shipping & return APIs for app_key=".config('aliexpress.app_key')." (product {$productId}, ship-to {$country})");
        $this->newLine();

        $rows = [];
        $firstShipping = null;

        foreach ($probes as $label => $probe) {
            $result = $client->call($probe['method'], $accessToken, $probe['params']);

            $rows[] = [
                strtoupper($probe['group']),
                $label,
                $probe['method'],
                $this->interpret($result),
                $result['code'] ?? '-',
                Str::limit((string) ($result['message'] ?? ''), 40),
            ];

            if ($result['ok']) {
                if ($probe['group'] === 'shipping' && $firstShipping === null) {
                    $firstShipping = [$probe['method'], $result['body']];
                }

                if ($this->option('dump')) {
                    $this->dump($probe['method'], $result['body']);
                }
            }
        }

        $this->table(['Group', 'Capability', 'API Method', 'Verdict', 'Code', 'Message'], $rows);

        if ($firstShipping !== null) {
            $this->newLine();
            $this->info("Shipping options discovered via {$firstShipping[0]}:");
            $this->renderFreight($firstShipping[1]);
        }

        $this->newLine();
        $this->line('Legend: ✅ allowed · ⛔ permission denied · ⚠️ allowed (param/data issue) · ❓ unknown');
        $this->line('Logs: storage/logs/aliexpress-*.log'.($this->option('dump') ? ' · dumps: storage/app/aliexpress/' : ''));

        return self::SUCCESS;
    }

    /**
     * Print the freight/shipping options found in a freight response.
     *
     * @param  array<string, mixed>  $body
     */
    protected function renderFreight(array $body): void
    {
        $paths = [
            'aliexpress_ds_freight_query_response.result.aeop_freight_calculate_result_for_buyer_d_t_o_list.aeop_freight_calculate_result_for_buyer_dto',
            'result.aeop_freight_calculate_result_for_buyer_d_t_o_list.aeop_freight_calculate_result_for_buyer_dto',
            'result.freight_list',
        ];

        $list = [];

        foreach ($paths as $path) {
            $found = data_get($body, $path);

            if (is_array($found) && $found !== []) {
                $list = array_is_list($found) ? $found : [$found];

                break;
            }
        }

        if ($list === []) {
            $this->line('  (response succeeded but no recognizable freight list — inspect the dump)');

            return;
        }

        foreach ($list as $opt) {
            if (! is_array($opt)) {
                continue;
            }

            $service = $opt['service_name'] ?? $opt['serviceName'] ?? '?';
            $fee = data_get($opt, 'freight.amount', $opt['freight_amount'] ?? '?');
            $currency = data_get($opt, 'freight.currency_code', $opt['currency'] ?? '');
            $time = $opt['estimated_delivery_time'] ?? $opt['delivery_time'] ?? '?';
            $free = $opt['free_shipping'] ?? $opt['freeShipping'] ?? '?';

            $this->line(sprintf('  • %-22s fee=%s %s, ETA=%s days, free=%s', $service, $fee, $currency, $time, var_export($free, true)));
        }
    }

    /**
     * @param  array<string, mixed>  $body
     */
    protected function dump(string $method, array $body): void
    {
        $dir = storage_path('app/aliexpress');
        File::ensureDirectoryExists($dir);

        $file = $dir.'/shipping_'.str_replace('.', '_', $method).'.json';
        File::put($file, json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->line("  saved: {$file}");
    }

    /**
     * @param  array{ok: bool, status: int, code: string|null, message: string|null, body: array<string, mixed>}  $result
     */
    protected function interpret(array $result): string
    {
        if ($result['ok']) {
            return '✅ allowed';
        }

        $code = strtolower((string) ($result['code'] ?? ''));
        $message = strtolower((string) ($result['message'] ?? ''));

        $permissionSignals = [
            'isperm', 'permission', 'unauthorized', 'not authorized',
            'no permission', 'apppermission', 'invalidapppermission',
            'accessdenied', 'app has no privilege', 'insufficientpermission',
        ];

        foreach ($permissionSignals as $signal) {
            if (str_contains($code, $signal) || str_contains($message, $signal)) {
                return '⛔ permission denied';
            }
        }

        if (str_contains($code, 'invalidapipath') || str_contains($message, 'invalid api path')) {
            return '✖ method not found';
        }

        $dataSignals = ['param', 'missing', 'invalid', 'not exist', 'illegal', 'order', 'null'];

        foreach ($dataSignals as $signal) {
            if (str_contains($code, $signal) || str_contains($message, $signal)) {
                return '⚠️ allowed (param/data)';
            }
        }

        return '❓ unknown';
    }
}
