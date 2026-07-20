<?php

namespace App\Console\Commands;

use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Probes the AliExpress APIs that back the dropshipping capabilities the
 * project needs, using the stored access token, and reports which ones the
 * current app key is actually allowed to call.
 *
 * Usage: php artisan aliexpress:check-permissions [--product-id=...]
 */
class AliExpressCheckPermissions extends Command
{
    protected $signature = 'aliexpress:check-permissions {--product-id= : An AliExpress product ID to probe product detail with}';

    protected $description = 'Verify which AliExpress dropshipping APIs the current app key/token can access';

    /**
     * Capability => [API method, minimal probe params].
     *
     * @var array<string, array{method: string, params: array<string, mixed>, note: string}>
     */
    protected array $probes = [];

    public function handle(AliExpressOAuthService $oauth, AliExpressApiClient $client): int
    {
        $token = $oauth->latestToken();

        if ($token === null) {
            $this->error('No AliExpress token found. Complete authorization at /aliexpress/connect first.');

            return self::FAILURE;
        }

        if (! $token->isAccessTokenValid()) {
            $this->warn('The stored access token appears expired and could not be refreshed. Re-authorize at /aliexpress/connect.');
        }

        $accessToken = $token->access_token;
        $productId = $this->option('product-id') ?: '1005006300000000';

        $this->probes = [
            'Import full product details' => [
                'method' => 'aliexpress.ds.product.get',
                'params' => [
                    'product_id' => $productId,
                    'ship_to_country' => 'US',
                    'target_currency' => 'USD',
                    'target_language' => 'en',
                ],
                'note' => 'Single product detail (used for import + price/stock sync).',
            ],
            'Price & stock sync (freight/shipping)' => [
                'method' => 'aliexpress.ds.freight.query',
                'params' => [
                    'queryDeliveryReq' => [
                        'productId' => $productId,
                        'shipToCountry' => 'US',
                        'quantity' => 1,
                        'currency' => 'USD',
                        'language' => 'en_US',
                        'locale' => 'en_US',
                    ],
                ],
                'note' => 'Shipping/availability query used during stock sync.',
            ],
            'Recommended/feed listing (bulk discovery)' => [
                'method' => 'aliexpress.ds.recommend.feed.get',
                'params' => [
                    'feed_name' => 'DS bestsellers',
                    'page_no' => 1,
                    'page_size' => 20,
                ],
                'note' => 'Paginated listing — closest thing to bulk import.',
            ],
            'Place order (order fulfillment)' => [
                'method' => 'aliexpress.ds.order.create',
                'params' => [
                    // Minimal structured payload: we only want to learn whether
                    // the method is PERMITTED, not actually place an order.
                    'param_place_order_request4_open_api_d_t_o' => [
                        'logistics_address' => ['country' => 'US'],
                        'product_items' => [],
                    ],
                ],
                'note' => 'Order creation. A "permission denied" here means fulfillment is NOT allowed.',
            ],
        ];

        $this->info('Probing AliExpress API permissions for app_key='.config('aliexpress.app_key').' ...');
        $this->newLine();

        $rows = [];

        foreach ($this->probes as $capability => $probe) {
            $result = $client->call($probe['method'], $accessToken, $probe['params']);

            $verdict = $this->interpret($result);

            $rows[] = [
                $capability,
                $probe['method'],
                $verdict['label'],
                $result['code'] ?? '-',
                Str::limit((string) ($result['message'] ?? ''), 50),
            ];
        }

        $this->table(
            ['Capability', 'API Method', 'Verdict', 'Code', 'Message'],
            $rows
        );

        $this->newLine();
        $this->line('Legend: ✅ allowed (call accepted) · ⛔ permission denied · ⚠️ allowed but bad params · ❓ unknown error');
        $this->line('Full details logged to storage/logs/aliexpress-*.log');

        return self::SUCCESS;
    }

    /**
     * Translate an API result into a permission verdict.
     *
     * @param  array{ok: bool, status: int, code: string|null, message: string|null, body: array<string, mixed>}  $result
     * @return array{label: string}
     */
    protected function interpret(array $result): array
    {
        if ($result['ok']) {
            return ['label' => '✅ allowed'];
        }

        $code = strtolower((string) ($result['code'] ?? ''));
        $message = strtolower((string) ($result['message'] ?? ''));

        // Permission / authorization related errors.
        $permissionSignals = [
            'isperm', 'permission', 'unauthorized', 'not authorized',
            'no permission', 'apppermission', 'invalidapppermission',
            'accessdenied', 'app has no privilege',
        ];

        foreach ($permissionSignals as $signal) {
            if (str_contains($code, $signal) || str_contains($message, $signal)) {
                return ['label' => '⛔ permission denied'];
            }
        }

        // Parameter errors mean the method itself IS callable (i.e. permitted).
        $paramSignals = ['param', 'missing', 'invalid', 'not exist', 'illegal'];

        foreach ($paramSignals as $signal) {
            if (str_contains($code, $signal) || str_contains($message, $signal)) {
                return ['label' => '⚠️ allowed (param issue)'];
            }
        }

        return ['label' => '❓ unknown'];
    }
}
