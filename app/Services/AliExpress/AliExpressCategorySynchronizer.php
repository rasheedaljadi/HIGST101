<?php

namespace App\Services\AliExpress;

use App\Exceptions\AliExpress\AliExpressImportException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Locale;

/**
 * Fetches the AliExpress category tree and mirrors selected categories into the
 * Bagisto catalog, linking each created category back to its AliExpress id via
 * categories.aliexpress_category_id.
 *
 * Top-level AliExpress categories are synced as children of the store root.
 * Individual (deeper) categories are resolved/created on demand during product
 * import via {@see self::resolveCategoryId()}, attaching them under their
 * top-level ancestor when possible (falling back to the root).
 *
 * Category names belong to a fixed taxonomy, so they are translated to Arabic
 * from a static, offline dictionary ({@see AliExpressCategoryDictionary}). No
 * external AI is used anywhere in the pipeline. Names with no dictionary entry
 * degrade gracefully to the English original.
 */
class AliExpressCategorySynchronizer
{
    public function __construct(
        protected AliExpressApiClient $apiClient,
        protected AliExpressOAuthService $oauthService,
    ) {}

    /**
     * Sync the top-level AliExpress categories into Bagisto.
     *
     * @return array{fetched:int, created:int, skipped:int}
     *
     * @throws AliExpressImportException
     */
    public function syncTopLevel(): array
    {
        $all = $this->fetchCategories();

        // Top-level = no parent_category_id (root nodes of the AliExpress tree).
        $top = array_values(array_filter(
            $all,
            fn ($c) => empty($c['parent_category_id'] ?? null) && ! empty($c['category_id']),
        ));

        $root = $this->rootCategory();

        // Translate all names up-front from the static dictionary (offline, no AI).
        $names = array_map(fn ($c) => (string) ($c['category_name'] ?? ''), $top);
        $translations = AliExpressCategoryDictionary::translateBatch($names);

        $created = 0;
        $skipped = 0;
        $position = (int) Category::where('parent_id', $root->id)->max('position');

        foreach ($top as $cat) {
            $aliId = (int) $cat['category_id'];

            if (Category::where('aliexpress_category_id', $aliId)->exists()) {
                $skipped++;

                continue;
            }

            $english = (string) ($cat['category_name'] ?? ('Category '.$aliId));
            $arabic = $translations[$english] ?? $english;

            $this->createCategory($aliId, $root->id, ++$position, $english, $arabic);
            $created++;
        }

        Log::channel('aliexpress')->info('AliExpress top-level categories synced', [
            'fetched' => count($top),
            'created' => $created,
            'skipped' => $skipped,
        ]);

        return ['fetched' => count($top), 'created' => $created, 'skipped' => $skipped];
    }

    /**
     * Sync the FULL AliExpress category tree into Bagisto, preserving the
     * parent/child hierarchy. Top-level categories become children of the store
     * root; deeper categories are nested under their AliExpress parent.
     *
     * @return array{fetched:int, created:int, skipped:int}
     *
     * @throws AliExpressImportException
     */
    public function syncFullTree(): array
    {
        $all = $this->indexById($this->fetchCategories());

        $root = $this->rootCategory();

        // Translate every category name from the static dictionary (offline, no
        // AI). Names absent from the dictionary keep their English original.
        $names = [];
        foreach ($all as $cat) {
            $names[] = (string) ($cat['category_name'] ?? '');
        }
        $translations = AliExpressCategoryDictionary::translateBatch($names);

        // aliexpressCategoryId => Bagisto category id, seeded from already-synced
        // categories so re-runs are incremental.
        $bagistoIdByAli = Category::whereNotNull('aliexpress_category_id')
            ->pluck('id', 'aliexpress_category_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $created = 0;
        $skipped = 0;

        // Resolve a node (and its ancestors) recursively, creating as needed so
        // each Bagisto parent_id mirrors the AliExpress hierarchy.
        $resolve = function (int $aliId) use (&$resolve, &$bagistoIdByAli, &$created, $all, $root, $translations): ?int {
            if (isset($bagistoIdByAli[$aliId])) {
                return $bagistoIdByAli[$aliId];
            }

            if (! isset($all[$aliId])) {
                return null;
            }

            $node = $all[$aliId];
            $parentAli = (int) ($node['parent_category_id'] ?? 0);

            $parentBagistoId = $root->id;
            if ($parentAli > 0 && isset($all[$parentAli])) {
                $parentBagistoId = $resolve($parentAli) ?? $root->id;
            }

            $english = (string) ($node['category_name'] ?? ('Category '.$aliId));
            $arabic = $translations[$english] ?? $english;
            $position = (int) Category::where('parent_id', $parentBagistoId)->max('position') + 1;

            $category = $this->createCategory($aliId, $parentBagistoId, $position, $english, $arabic);
            $bagistoIdByAli[$aliId] = (int) $category->id;
            $created++;

            return (int) $category->id;
        };

        foreach (array_keys($all) as $aliId) {
            if (isset($bagistoIdByAli[$aliId])) {
                $skipped++;

                continue;
            }
            $resolve((int) $aliId);
        }

        Log::channel('aliexpress')->info('AliExpress full category tree synced', [
            'fetched' => count($all),
            'created' => $created,
            'skipped' => $skipped,
        ]);

        return ['fetched' => count($all), 'created' => $created, 'skipped' => $skipped];
    }

    /**
     * Resolve the Bagisto category id for an AliExpress category id. With the
     * full tree synced this is normally a fast local lookup; if the id is not
     * stored (a deep leaf the API omitted) it walks up to the nearest synced
     * ancestor, returning null when nothing matches (caller falls back).
     */
    public function resolveCategoryId(int $aliexpressCategoryId): ?int
    {
        if ($aliexpressCategoryId <= 0) {
            return null;
        }

        $existing = Category::where('aliexpress_category_id', $aliexpressCategoryId)->first();

        if ($existing) {
            return (int) $existing->id;
        }

        // Not synced yet: fetch the tree, locate the node, and create it under
        // its top-level ancestor (creating that ancestor too if needed).
        try {
            $all = $this->indexById($this->fetchCategories());
        } catch (\Throwable $e) {
            Log::channel('aliexpress')->warning('Could not resolve AliExpress category; using default', [
                'aliexpress_category_id' => $aliexpressCategoryId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! isset($all[$aliexpressCategoryId])) {
            return null;
        }

        $root = $this->rootCategory();

        // Walk up to the top-level ancestor.
        $node = $all[$aliexpressCategoryId];
        $ancestor = $node;

        while (! empty($ancestor['parent_category_id'] ?? null) && isset($all[(int) $ancestor['parent_category_id']])) {
            $ancestor = $all[(int) $ancestor['parent_category_id']];
        }

        // Ensure the top-level ancestor exists in Bagisto.
        $ancestorId = (int) $ancestor['category_id'];
        $parentBagisto = Category::where('aliexpress_category_id', $ancestorId)->first();

        if (! $parentBagisto) {
            $english = (string) ($ancestor['category_name'] ?? ('Category '.$ancestorId));
            $arabic = AliExpressCategoryDictionary::translate($english) ?? $english;
            $position = (int) Category::where('parent_id', $root->id)->max('position') + 1;
            $parentBagisto = $this->createCategory($ancestorId, $root->id, $position, $english, $arabic);
        }

        // If the node itself IS the top-level ancestor, we are done.
        if ($ancestorId === $aliexpressCategoryId) {
            return (int) $parentBagisto->id;
        }

        // Create the leaf node under its top-level ancestor.
        $english = (string) ($node['category_name'] ?? ('Category '.$aliexpressCategoryId));
        $arabic = AliExpressCategoryDictionary::translate($english) ?? $english;
        $position = (int) Category::where('parent_id', $parentBagisto->id)->max('position') + 1;
        $leaf = $this->createCategory($aliexpressCategoryId, (int) $parentBagisto->id, $position, $english, $arabic);

        return (int) $leaf->id;
    }

    /**
     * Re-translate existing synced categories' Arabic names (e.g. after the
     * translation provider's quota resets). Updates the AR translation in place
     * for every category linked to an AliExpress id.
     *
     * @return array{updated:int, total:int}
     */
    public function retranslateExisting(): array
    {
        $categories = Category::whereNotNull('aliexpress_category_id')->get();

        // Collect the English source names (from the en translation).
        $sources = [];

        foreach ($categories as $category) {
            $en = $category->translations->firstWhere('locale', 'en');
            $name = $en?->name;

            if (is_string($name) && trim($name) !== '') {
                $sources[$category->id] = $name;
            }
        }

        $translations = AliExpressCategoryDictionary::translateBatch(array_values(array_unique($sources)));

        $updated = 0;

        foreach ($categories as $category) {
            $english = $sources[$category->id] ?? null;

            if ($english === null) {
                continue;
            }

            $arabic = $translations[$english] ?? $english;

            if ($arabic === $english) {
                continue; // No translation available; leave as-is.
            }

            $ar = $category->translations->firstWhere('locale', 'ar')
                ?? $category->translations->firstWhere('locale', 'AR');

            if ($ar) {
                $ar->forceFill([
                    'name' => $arabic,
                    'description' => $arabic,
                    'meta_title' => $arabic,
                    'meta_description' => $arabic,
                    'meta_keywords' => $arabic,
                ])->save();
                $updated++;
            }
        }

        Log::channel('aliexpress')->info('AliExpress category re-translation complete', [
            'updated' => $updated,
            'total' => $categories->count(),
        ]);

        return ['updated' => $updated, 'total' => $categories->count()];
    }

    /**
     * Create a Bagisto category linked to an AliExpress category id, with a
     * translation row per store locale (Arabic where available, English else).
     */
    protected function createCategory(int $aliId, int $parentId, int $position, string $english, string $arabic): Category
    {
        $category = new Category([
            'position' => $position,
            'status' => 1,
            'display_mode' => 'products_and_description',
        ]);

        $category->parent_id = $parentId;
        $category->aliexpress_category_id = $aliId;
        $category->save();

        $baseSlug = Str::slug($english) ?: ('ae-cat-'.$aliId);

        foreach (Locale::all() as $locale) {
            $isArabic = strtolower($locale->code) === 'ar';
            $name = $isArabic ? $arabic : $english;

            // Unique slug per locale to satisfy the
            // (category_id, slug, locale) unique index without cross-locale or
            // cross-category collisions.
            $slug = $this->uniqueSlug($baseSlug.'-'.strtolower($locale->code), $aliId);

            $translation = $category->translations()->create([
                'locale_id' => $locale->id,
                'name' => $name,
                'slug' => $slug,
                'description' => $name,
                'meta_title' => $name,
                'meta_description' => $name,
                'meta_keywords' => $name,
            ]);

            // `locale` is not in the translation model's fillable, so set it
            // explicitly (Bagisto reads translations by locale code in places).
            if (isset($translation->locale) === false || $translation->locale === '' || $translation->locale === null) {
                $translation->forceFill(['locale' => $locale->code])->save();
            }
        }

        return $category;
    }

    /**
     * Ensure the slug is unique across category translations.
     */
    protected function uniqueSlug(string $base, int $aliId): string
    {
        $slug = $base;

        if (Category::whereTranslation('slug', $slug)->exists()) {
            $slug = $base.'-'.$aliId;
        }

        $suffix = 1;

        while (Category::whereTranslation('slug', $slug)->exists()) {
            $slug = $base.'-'.$aliId.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * Fetch the raw AliExpress category list.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws AliExpressImportException
     */
    protected function fetchCategories(): array
    {
        $token = $this->oauthService->latestToken();

        if ($token === null || ! $token->isAccessTokenValid()) {
            throw new AliExpressImportException('AliExpress authorization required to fetch categories.');
        }

        $method = (string) config('aliexpress.category.method', 'aliexpress.ds.category.get');

        $result = $this->apiClient->call($method, $token->access_token, []);

        if ($result['ok'] === false) {
            throw new AliExpressImportException(
                'Failed to fetch AliExpress categories: '.($result['message'] ?? 'unknown error').'.',
                ['code' => $result['code']],
            );
        }

        $body = $result['body']['aliexpress_ds_category_get_response'] ?? $result['body'];
        $categories = data_get($body, 'resp_result.result.categories.category', []);

        return is_array($categories) ? array_values($categories) : [];
    }

    /**
     * Index categories by their AliExpress id.
     *
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<int, array<string, mixed>>
     */
    protected function indexById(array $categories): array
    {
        $index = [];

        foreach ($categories as $cat) {
            if (! empty($cat['category_id'])) {
                $index[(int) $cat['category_id']] = $cat;
            }
        }

        return $index;
    }

    /**
     * The store's root category.
     *
     * @throws AliExpressImportException
     */
    protected function rootCategory(): Category
    {
        $root = Category::whereNull('parent_id')->orderBy('_lft')->first();

        if ($root === null) {
            throw new AliExpressImportException('Root category not found.');
        }

        return $root;
    }
}
