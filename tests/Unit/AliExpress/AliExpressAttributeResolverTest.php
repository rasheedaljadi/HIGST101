<?php

use App\Services\AliExpress\AliExpressAttributeResolver;
use App\Services\AliExpress\DTO\NormalizedVariantAxis;
use App\Services\AliExpress\DTO\ResolvedAxes;
use Tests\TestCase;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;

/*
|--------------------------------------------------------------------------
| AliExpressAttributeResolver tests (DB-backed)
|--------------------------------------------------------------------------
|
| Covers Requirements 8.1, 8.2, 8.3, 8.4: resolving normalized variant axes
| against Bagisto's EAV attribute system. The resolver finds-or-creates
| configurable `select` attributes and their options, reuses existing
| records (case-insensitively for option labels), and returns numeric
| attribute_options ids owned by their attribute.
|
| These tests touch the database (AttributeRepository / AttributeOptionRepository),
| so they boot the application and run inside a transaction via Tests\TestCase
| (DatabaseTransactions) — the project's established DB test convention.
|
*/

uses(TestCase::class);

/**
 * Generate a unique-enough axis code suffix so repeated test runs against the
 * shared dev/seeded database never collide with attributes left by a prior run.
 */
function uniqueAxisSuffix(): string
{
    return substr(str_replace('.', '', uniqid('', true)), -8);
}

beforeEach(function () {
    $this->resolver = app(AliExpressAttributeResolver::class);
    $this->attributeRepository = app(AttributeRepository::class);
    $this->attributeOptionRepository = app(AttributeOptionRepository::class);
});

it('creates a missing attribute as a configurable select with its options (Req 8.1)', function () {
    $code = 'ae_color_'.uniqueAxisSuffix();

    $axis = new NormalizedVariantAxis('Color', $code, ['Red', 'Blue']);

    $resolved = $this->resolver->resolveAxes([$axis]);

    expect($resolved)->toBeInstanceOf(ResolvedAxes::class);

    // Read straight from the DB (bypassing the cacheable repository) to confirm
    // the resolver persisted the attribute as expected.
    $attribute = Attribute::query()->where('code', $code)->first();

    expect($attribute)->not->toBeNull()
        ->and($attribute->type)->toBe('select')
        ->and((bool) $attribute->is_configurable)->toBeTrue();

    // Options were created with the correct labels.
    $labels = $attribute->options()->get()->pluck('admin_name')->all();

    expect($labels)->toContain('Red')
        ->and($labels)->toContain('Blue')
        ->and($labels)->toHaveCount(2);
});

it('reuses an existing configurable select attribute instead of duplicating it (Req 8.4)', function () {
    $code = 'ae_size_'.uniqueAxisSuffix();

    // Pre-create the attribute the resolver should reuse.
    $existing = $this->attributeRepository->create([
        'code' => $code,
        'admin_name' => 'Size',
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
        'options' => [
            ['admin_name' => 'S', 'sort_order' => 0],
        ],
    ]);

    $countBefore = $this->attributeRepository->count();

    $axis = new NormalizedVariantAxis('Size', $code, ['S', 'M']);

    $resolved = $this->resolver->resolveAxes([$axis]);

    // No new attribute row was created.
    expect($this->attributeRepository->count())->toBe($countBefore);

    // The same attribute id is reused and surfaced in attributesByCode.
    expect($resolved->attributesByCode)->toHaveKey($code)
        ->and((int) $resolved->attributesByCode[$code]->id)->toBe((int) $existing->id);
});

it('reuses an option case-insensitively rather than creating a duplicate (Req 8.4)', function () {
    $code = 'ae_color_'.uniqueAxisSuffix();

    // Existing attribute already owns an option labelled "Red".
    $existing = $this->attributeRepository->create([
        'code' => $code,
        'admin_name' => 'Color',
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
        'options' => [
            ['admin_name' => 'Red', 'sort_order' => 0],
        ],
    ]);

    $existingOption = $existing->options()->first();

    $optionCountBefore = $existing->options()->count();

    // Axis value differs only by case ("red" vs "Red").
    $axis = new NormalizedVariantAxis('Color', $code, ['red']);

    $resolved = $this->resolver->resolveAxes([$axis]);

    // No duplicate option was created.
    expect($existing->options()->count())->toBe($optionCountBefore);

    // The reused option id is the pre-existing one.
    expect($resolved->superAttributes[$code])->toBe([(int) $existingOption->id])
        ->and($resolved->optionIdLookup[$code]['red'])->toBe((int) $existingOption->id);
});

it('returns numeric option ids owned by the attribute and maps code => ids (Req 8.2, 8.3)', function () {
    $code = 'ae_material_'.uniqueAxisSuffix();

    $axis = new NormalizedVariantAxis('Material', $code, ['Cotton', 'Wool']);

    $resolved = $this->resolver->resolveAxes([$axis]);

    $attribute = $resolved->attributesByCode[$code];

    $optionIds = $resolved->superAttributes[$code];

    expect($optionIds)->toHaveCount(2);

    // The set of option ids owned by the attribute in the DB.
    $ownedIds = $attribute->options()->pluck('id')->map(fn ($id) => (int) $id)->all();

    foreach ($optionIds as $optionId) {
        expect($optionId)->toBeInt()
            ->and($optionId)->toBeGreaterThan(0)
            ->and($ownedIds)->toContain($optionId);

        // The id genuinely exists in attribute_options for this attribute.
        $owned = $this->attributeOptionRepository
            ->findOneWhere(['id' => $optionId, 'attribute_id' => $attribute->id]);

        expect($owned)->not->toBeNull();
    }

    // optionIdLookup maps each label to one of the returned ids.
    foreach (['Cotton', 'Wool'] as $label) {
        expect($resolved->optionIdLookup[$code][$label])->toBeInt()
            ->and($optionIds)->toContain($resolved->optionIdLookup[$code][$label]);
    }
});
