<?php

namespace App\Console\Commands;

use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Explores the AliExpress attribute/property APIs for a given category, probing
 * the candidate dropshipping endpoints that expose a category's attribute
 * definitions (e.g. Color, Size, Material and their allowed values).
 *
 * AliExpress does not publish a single canonical "category attributes" method
 * across every app permission set, so this command tries several known method
 * names and reports which one the current app key can actually call, then dumps
 * the discovered attribute structure (and saves the raw JSON for inspection).
 *
 * Usage:
 *   php artisan aliexpress:explore-attributes --category-id=3
 *   php artisan aliexpress:explore-attributes --category-id=200000343 --dump
 */
class AliExpressExploreAttributes extends Command
{
    protected $signature = 'aliexpress:explore-attributes
        {--category-id= : AliExpress category id to inspect (defaults to 3 = Apparel)}
        {--language=en : Locale passed to the API (e.g. en, ar)}
        {--dump : Save each successful raw JSON response under storage/app/aliexpress/}';

    protected $description = 'Probe AliExpress category-attribute APIs and dump the attribute definitions for a category';

    /**
     * Candidate methods that may return a category's attribute definitions.
     * Each entry maps the API method to the params it expects (the category id
     * placeholder is filled at runtime).
     *
     * @var array<int, array{method: string, params: array<string, mixed>, note: string}>
     */
    protected array $candidates = [];

    public function handle(AliExpressOAuthService $oauth, AliExpressApiClient $client): int
    {
        $token = $oauth->latestToken();

        if ($token === null) {
            $this->error('No AliExpress token found. Complete authorization at /aliexpress/connect first.');

            return self::FAILURE;
        }

        if (! $token->isAccessTokenValid()) {
            $this->warn('Stored access token appears expired and could not be refreshed. Re-authorize at /aliexpress/connect.');
        }

        $accessToken = $token->access_token;
        $categoryId = (string) ($this->option('category-id') ?: '3');
        $language = (string) ($this->option('language') ?: 'en');

        $this->candidates = [
            [
                'method' => 'aliexpress.ds.category.attributes.get',
                'params' => ['category_id' => $categoryId, 'language' => $language],
                'note' => 'DS category attribute definitions.',
            ],
            [
                'method' => 'aliexpress.ds.category.attribute.get',
                'params' => ['category_id' => $categoryId, 'language' => $language],
                'note' => 'DS category attribute definitions (singular).',
            ],
            [
                'method' => 'aliexpress.ds.category.attr.get',
                'params' => ['category_id' => $categoryId, 'language' => $language],
                'note' => 'DS category attr (short form).',
            ],
            [
                'method' => 'aliexpress.category.redefining.getpostcategorybyid',
                'params' => ['param0' => $categoryId],
                'note' => 'Post-category definition (param0 = category id).',
            ],
            [
                'method' => 'aliexpress.postproduct.redefining.getchildattributesresultbyidandlocale',
                'params' => ['cateId' => $categoryId, 'locale' => $language],
                'note' => 'Child attribute results by category id + locale (listing API).',
            ],
            [
                'method' => 'aliexpress.postproduct.redefining.getchildattributesresultbyidandlocale',
                'params' => ['param0' => $categoryId, 'param1' => $language],
                'note' => 'Child attribute results (param0/param1 positional).',
            ],
            [
                'method' => 'aliexpress.solution.product.schema.get',
                'params' => ['aliexpress_category_id' => $categoryId],
                'note' => 'Product schema for a category (solution API).',
            ],
            [
                'method' => 'aliexpress.solution.sellercategorytree.query',
                'params' => ['category_id' => $categoryId],
                'note' => 'Seller category tree query.',
            ],
        ];

        $this->info("Probing AliExpress attribute APIs for category_id={$categoryId} (language={$language})");
        $this->line('app_key='.config('aliexpress.app_key'));
        $this->newLine();

        $rows = [];
        $firstSuccess = null;

        foreach ($this->candidates as $candidate) {
            $result = $client->call($candidate['method'], $accessToken, $candidate['params']);

            $verdict = $this->interpret($result);

            $rows[] = [
                $candidate['method'],
                $verdict,
                $result['code'] ?? '-',
                Str::limit((string) ($result['message'] ?? ''), 45),
            ];

            if ($result['ok']) {
                if ($firstSuccess === null) {
                    $firstSuccess = [$candidate['method'], $result['body']];
                }

                if ($this->option('dump')) {
                    $this->dump($candidate['method'], $categoryId, $result['body']);
                }

                $this->renderAttributes($candidate['method'], $result['body']);
            }
        }

        $this->newLine();
        $this->table(['API Method', 'Verdict', 'Code', 'Message'], $rows);
        $this->line('Legend: ✅ allowed · ⛔ permission denied · ⚠️ allowed (param issue) · ❓ unknown');
        $this->line('Raw logs: storage/logs/aliexpress-*.log'.($this->option('dump') ? ' · JSON dumps: storage/app/aliexpress/' : ''));

        if ($firstSuccess === null) {
            $this->newLine();
            $this->warn('No attribute API returned a successful response. Note the codes/messages above to decide the right method and required params.');
        }

        return self::SUCCESS;
    }

    /**
     * Pretty-print the attribute names + sample values discovered in a body,
     * scanning common AliExpress attribute container shapes.
     *
     * @param  array<string, mixed>  $body
     */
    protected function renderAttributes(string $method, array $body): void
    {
        $this->newLine();
        $this->info("Attributes discovered via {$method}:");

        $attributes = $this->locateAttributes($body);

        if ($attributes === []) {
            $this->line('  (response succeeded but no recognizable attribute list was found — inspect the dump)');

            return;
        }

        foreach ($attributes as $attr) {
            $name = $attr['name'] ?? '(unnamed)';
            $id = $attr['id'] ?? '-';
            $required = $attr['required'] ? 'required' : 'optional';
            $type = $attr['type'] ?? '-';
            $values = $attr['values'];

            $this->line(sprintf('  • <comment>%s</comment> [id=%s, %s, type=%s] — %d values', $name, $id, $required, $type, count($values)));

            if ($values !== []) {
                $this->line('      '.implode(' | ', array_slice($values, 0, 20)).(count($values) > 20 ? ' …' : ''));
            }
        }
    }

    /**
     * Try to locate and normalize a list of attribute definitions from any of
     * the known AliExpress response shapes.
     *
     * @param  array<string, mixed>  $body
     * @return array<int, array{name:?string, id:mixed, required:bool, type:?string, values:array<int,string>}>
     */
    protected function locateAttributes(array $body): array
    {
        // Known container paths across the candidate APIs.
        $paths = [
            'aliexpress_ds_category_attributes_get_response.result.attributes.attribute',
            'aliexpress_ds_category_attribute_get_response.result.attributes.attribute',
            'aliexpress_postproduct_redefining_getchildattributesresultbyidandlocale_response.result.aeop_attribute_dto_list.aeop_attribute_dto',
            'result.attributes.attribute',
            'result.aeop_attribute_dto_list.aeop_attribute_dto',
            'attributes.attribute',
        ];

        $list = [];

        foreach ($paths as $path) {
            $found = Arr::get($body, $path);

            if (is_array($found) && $found !== []) {
                $list = array_is_list($found) ? $found : [$found];

                break;
            }
        }

        $normalized = [];

        foreach ($list as $attr) {
            if (! is_array($attr)) {
                continue;
            }

            $name = $attr['attr_name'] ?? $attr['attribute_name'] ?? $attr['name'] ?? $attr['en_name'] ?? null;
            $id = $attr['attr_id'] ?? $attr['attribute_id'] ?? $attr['id'] ?? null;
            $required = (bool) ($attr['required'] ?? $attr['is_required'] ?? false);
            $type = $attr['attr_show_type_value'] ?? $attr['show_type'] ?? $attr['input_type'] ?? $attr['type'] ?? null;

            $normalized[] = [
                'name' => is_scalar($name) ? (string) $name : null,
                'id' => $id,
                'required' => $required,
                'type' => is_scalar($type) ? (string) $type : null,
                'values' => $this->locateValues($attr),
            ];
        }

        return $normalized;
    }

    /**
     * Extract the allowed value labels for one attribute across known shapes.
     *
     * @param  array<string, mixed>  $attr
     * @return array<int, string>
     */
    protected function locateValues(array $attr): array
    {
        $valuePaths = [
            'attr_values.attr_value',
            'values.value',
            'aeop_attribute_value_dtos.aeop_attribute_value_dto',
            'attribute_values.attribute_value',
        ];

        $raw = [];

        foreach ($valuePaths as $path) {
            $found = Arr::get($attr, $path);

            if (is_array($found) && $found !== []) {
                $raw = array_is_list($found) ? $found : [$found];

                break;
            }
        }

        $labels = [];

        foreach ($raw as $value) {
            if (is_string($value)) {
                $labels[] = $value;

                continue;
            }

            if (is_array($value)) {
                $label = $value['attr_value'] ?? $value['en_name'] ?? $value['name'] ?? $value['value'] ?? null;

                if (is_scalar($label)) {
                    $labels[] = (string) $label;
                }
            }
        }

        return array_values(array_unique(array_filter($labels, fn ($l) => trim($l) !== '')));
    }

    /**
     * Save a raw successful response to storage/app/aliexpress/ for inspection.
     *
     * @param  array<string, mixed>  $body
     */
    protected function dump(string $method, string $categoryId, array $body): void
    {
        $dir = storage_path('app/aliexpress');
        File::ensureDirectoryExists($dir);

        $file = $dir.'/attributes_'.$categoryId.'_'.str_replace('.', '_', $method).'.json';

        File::put($file, json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->line("  saved: {$file}");
    }

    /**
     * Translate an API result into a permission verdict (mirrors check-permissions).
     *
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
            'accessdenied', 'app has no privilege',
        ];

        foreach ($permissionSignals as $signal) {
            if (str_contains($code, $signal) || str_contains($message, $signal)) {
                return '⛔ permission denied';
            }
        }

        $paramSignals = ['param', 'missing', 'invalid', 'not exist', 'illegal'];

        foreach ($paramSignals as $signal) {
            if (str_contains($code, $signal) || str_contains($message, $signal)) {
                return '⚠️ allowed (param issue)';
            }
        }

        return '❓ unknown';
    }
}
