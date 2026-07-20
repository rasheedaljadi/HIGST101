<?php

namespace App\Services\AliExpress;

use App\Exceptions\AliExpress\AliExpressImportException;
use App\Services\AliExpress\DTO\NormalizedVariantAxis;
use App\Services\AliExpress\DTO\ResolvedAxes;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Contracts\Attribute as AttributeContract;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;

/**
 * Resolves normalized AliExpress variant axes against Bagisto's EAV attribute
 * system, finding or creating the configurable `select` attributes and their
 * options needed to build a configurable product.
 *
 * For each axis the resolver:
 *  - reuses an existing configurable `select` attribute with the axis code;
 *  - falls back to a distinct `<code>_var` code when an attribute with that
 *    code exists but is not a configurable select (so core attributes are
 *    never corrupted);
 *  - otherwise creates a new configurable `select` attribute with one option
 *    per distinct value.
 *
 * Option labels are matched case-insensitively and trimmed to maximize reuse
 * and avoid duplicate options. Returned values are always numeric
 * `attribute_options.id`s (Requirement 8).
 */
class AliExpressAttributeResolver
{
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AttributeOptionRepository $attributeOptionRepository,
    ) {}

    /**
     * Resolve the given axes into super-attributes, an option-id lookup, and
     * the attribute models keyed by code.
     *
     * @param  NormalizedVariantAxis[]  $axes
     *
     * @throws AliExpressImportException on truly unexpected EAV states.
     */
    public function resolveAxes(array $axes): ResolvedAxes
    {
        $superAttributes = [];
        $optionIdLookup = [];
        $attributesByCode = [];

        foreach ($axes as $axis) {
            $attribute = $this->resolveAttribute($axis);

            $code = $attribute->code;

            $attributesByCode[$code] = $attribute;

            // Build a normalized label => id index from the attribute's
            // existing options so we can reuse and detect missing ones.
            $optionIndex = $this->buildOptionIndex($attribute);

            $optionIds = [];

            foreach ($this->distinctValues($axis->values) as $label) {
                $normalized = $this->normalizeLabel($label);

                if (! isset($optionIndex[$normalized])) {
                    $optionIndex[$normalized] = $this->createOption($attribute, $label, $axis->code);
                }

                $optionId = $optionIndex[$normalized];

                $optionIdLookup[$code][$label] = $optionId;

                $optionIds[] = $optionId;
            }

            $superAttributes[$code] = array_values(array_unique($optionIds));
        }

        return new ResolvedAxes($superAttributes, $optionIdLookup, $attributesByCode);
    }

    /**
     * Find or create the configurable `select` attribute backing an axis.
     *
     * @throws AliExpressImportException when an attribute cannot be created.
     */
    protected function resolveAttribute(NormalizedVariantAxis $axis): AttributeContract
    {
        $code = $axis->code;

        $attribute = $this->findAttributeByCode($code);

        // An existing attribute that is not a configurable select must not be
        // touched; fall back to a distinct, AliExpress-owned code instead.
        if ($attribute !== null && ! $this->isReusable($attribute)) {
            $code = $code.'_var';

            $attribute = $this->findAttributeByCode($code);
        }

        if ($attribute === null) {
            $attribute = $this->createSelectAttribute($code, $axis->name, $axis->values);
        }

        if (! $attribute instanceof AttributeContract) {
            throw new AliExpressImportException(
                'Failed to resolve a variant attribute for the imported product.',
                ['attribute_code' => $code, 'axis' => $axis->name],
            );
        }

        return $attribute;
    }

    /**
     * Look up an attribute by its code, returning null when absent.
     *
     * The lookup deliberately bypasses the repository cache (skipCache). The
     * project's cache driver is `file`, which does not support cache tags, so
     * Bagisto's CacheableRepository cannot flush a cached entry on attribute
     * creation. Without skipCache this "does it already exist?" probe would
     * cache a `null` miss; Bagisto's Configurable::create() (via
     * getAttributeByCode) would then read that same stale `null` for the
     * attribute we just created and fail with "Attempt to read property code
     * on null". Skipping the cache here keeps the miss out of the cache so the
     * subsequent lookup queries the freshly-created row.
     */
    protected function findAttributeByCode(string $code): ?AttributeContract
    {
        return $this->attributeRepository->skipCache()->findOneByField('code', $code);
    }

    /**
     * An attribute is reusable as a configurable axis only when it is a
     * configurable `select` attribute.
     */
    protected function isReusable(AttributeContract $attribute): bool
    {
        return $attribute->type === 'select' && (bool) $attribute->is_configurable;
    }

    /**
     * Create a configurable `select` attribute with one option per distinct
     * value, mirroring the shape the Bagisto AttributeRepository expects.
     *
     * @param  string[]  $values
     */
    protected function createSelectAttribute(string $code, string $name, array $values): AttributeContract
    {
        $options = [];

        foreach ($this->distinctValues($values) as $label) {
            $options[] = $this->optionPayload($label, $code);
        }

        $attribute = $this->attributeRepository->create([
            'code' => $code,
            'admin_name' => $name !== '' ? $name : $code,
            'type' => 'select',
            'is_configurable' => 1,
            'is_required' => 0,
            'is_unique' => 0,
            'is_filterable' => 0,
            'is_comparable' => 0,
            'is_visible_on_front' => 0,
            'value_per_locale' => 0,
            'value_per_channel' => 0,
            'validation' => '',
            'position' => 0,
            // Render variants as visual swatches (color circles / text chips)
            // on the storefront instead of a dropdown, matching AliExpress.
            'swatch_type' => AliExpressAttributeDictionary::swatchTypeForAxis($name !== '' ? $name : $code),
            'options' => $options,
        ]);

        // Map the new attribute into every attribute family's first group so
        // it surfaces on the admin product edit page (configurable variation
        // matrix). Without a family-group mapping, Bagisto's edit form does not
        // render the attribute at all, so color/size would be invisible there.
        $this->mapAttributeToFamilies($attribute);

        // Create the attribute name translation for every store locale so the
        // admin/storefront show a readable label (e.g. "Color"/"اللون") instead
        // of a blank name.
        $this->translateAttributeName($attribute, $name !== '' ? $name : $code);

        return $attribute;
    }

    /**
     * Ensure the attribute has a `name` translation for every store locale.
     *
     * The Arabic locale name is rendered from the offline dictionary
     * ({@see AliExpressAttributeDictionary}) so the axis label shows in Arabic
     * (e.g. "Color" => "اللون"); other locales keep the original name.
     */
    protected function translateAttributeName(AttributeContract $attribute, string $name): void
    {
        $arabic = AliExpressAttributeDictionary::translateAxisName($name);

        foreach ($this->localeCodes() as $localeCode) {
            $isArabic = strtolower($localeCode) === 'ar';

            DB::table('attribute_translations')->updateOrInsert(
                ['attribute_id' => $attribute->id, 'locale' => $localeCode],
                ['name' => $isArabic ? $arabic : $name],
            );
        }
    }

    /**
     * Attach a newly created attribute to the first attribute group of every
     * attribute family, so it is editable on the product edit page.
     */
    protected function mapAttributeToFamilies(AttributeContract $attribute): void
    {
        $groups = AttributeGroup::query()
            ->orderBy('attribute_family_id')
            ->orderBy('position')
            ->get()
            ->groupBy('attribute_family_id');

        foreach ($groups as $familyGroups) {
            $group = $familyGroups->first();

            if ($group === null) {
                continue;
            }

            $exists = DB::table('attribute_group_mappings')
                ->where('attribute_id', $attribute->id)
                ->where('attribute_group_id', $group->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $position = (int) DB::table('attribute_group_mappings')
                ->where('attribute_group_id', $group->id)
                ->max('position');

            DB::table('attribute_group_mappings')->insert([
                'attribute_id' => $attribute->id,
                'attribute_group_id' => $group->id,
                'position' => $position + 1,
            ]);
        }
    }

    /**
     * Create a single option for an existing attribute and return its id.
     */
    protected function createOption(AttributeContract $attribute, string $label, string $axisCode = ''): int
    {
        $option = $this->attributeOptionRepository->create(array_merge(
            ['attribute_id' => $attribute->id],
            $this->optionPayload($label, $axisCode !== '' ? $axisCode : (string) $attribute->code),
        ));

        return (int) $option->id;
    }

    /**
     * Build the create payload for an option: an admin name plus a per-locale
     * translated label for every store locale.
     *
     * The Arabic locale label is rendered from the offline attribute dictionary
     * ({@see AliExpressAttributeDictionary}) so colors/sizes display in Arabic
     * (e.g. "black" => "أسود") without any AI call. Non-Arabic locales keep the
     * original English value; unknown values fall back to the original.
     *
     * @return array<string, mixed>
     */
    protected function optionPayload(string $label, string $axisCode = ''): array
    {
        $payload = [
            'admin_name' => $label,
            'sort_order' => 0,
        ];

        // For color axes, attach a HEX swatch value so the storefront renders a
        // real color circle. Unknown colors get no value (the 'color' swatch
        // then shows an empty circle; acceptable and still selectable).
        if (AliExpressAttributeDictionary::swatchTypeForAxis($axisCode) === 'color') {
            $hex = AliExpressAttributeDictionary::colorHex($label);

            if ($hex !== null) {
                $payload['swatch_value'] = $hex;
            }
        }

        $arabic = AliExpressAttributeDictionary::translate($label, $axisCode);

        foreach ($this->localeCodes() as $localeCode) {
            $isArabic = strtolower($localeCode) === 'ar';

            $payload[$localeCode] = ['label' => $isArabic ? $arabic : $label];
        }

        return $payload;
    }

    /**
     * Build a normalized-label => option-id index for an attribute's existing
     * options, indexing by both the admin name and every translated label.
     *
     * @return array<string, int>
     */
    protected function buildOptionIndex(AttributeContract $attribute): array
    {
        $index = [];

        foreach ($attribute->options()->with('translations')->get() as $option) {
            foreach ($this->optionLabels($option) as $label) {
                $normalized = $this->normalizeLabel($label);

                if ($normalized === '') {
                    continue;
                }

                // Keep the first id seen for a given label.
                $index[$normalized] ??= (int) $option->id;
            }
        }

        return $index;
    }

    /**
     * Collect all candidate labels for an option (admin name + translations).
     *
     * @return string[]
     */
    protected function optionLabels(object $option): array
    {
        $labels = [];

        if (is_string($option->admin_name) && $option->admin_name !== '') {
            $labels[] = $option->admin_name;
        }

        foreach ($option->translations as $translation) {
            if (is_string($translation->label) && $translation->label !== '') {
                $labels[] = $translation->label;
            }
        }

        return $labels;
    }

    /**
     * The store's locale codes used when writing option translations.
     *
     * @return string[]
     */
    protected function localeCodes(): array
    {
        return core()->getAllLocales()->pluck('code')->all();
    }

    /**
     * Normalize a label for case-insensitive, trimmed matching.
     */
    protected function normalizeLabel(string $label): string
    {
        return mb_strtolower(trim($label));
    }

    /**
     * Distinct values preserving order, compared case-insensitively/trimmed.
     *
     * @param  string[]  $values
     * @return string[]
     */
    protected function distinctValues(array $values): array
    {
        $seen = [];
        $distinct = [];

        foreach ($values as $value) {
            $normalized = $this->normalizeLabel($value);

            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $distinct[] = $value;
        }

        return $distinct;
    }
}
