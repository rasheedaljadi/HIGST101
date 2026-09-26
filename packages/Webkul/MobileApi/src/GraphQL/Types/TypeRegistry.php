<?php

namespace Webkul\MobileApi\GraphQL\Types;

use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\VariableNode;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class TypeRegistry
{
    protected static array $types = [];

    public static function json(): CustomScalarType
    {
        return self::$types['JSON'] ??= new CustomScalarType([
            'name' => 'JSON',
            'description' => 'Arbitrary JSON object or array',
            'serialize' => fn ($value) => is_string($value) ? json_decode($value, true) : $value,
            'parseValue' => fn ($value) => $value,
            'parseLiteral' => fn ($ast, ?array $variables = null) => self::parseLiteralValue($ast, $variables),
        ]);
    }

    public static function iterable(): CustomScalarType
    {
        return self::$types['Iterable'] ??= new CustomScalarType([
            'name' => 'Iterable',
            'description' => 'Arbitrary iterable array or list',
            'serialize' => fn ($value) => $value,
            'parseValue' => fn ($value) => $value,
            'parseLiteral' => fn ($ast, ?array $variables = null) => self::parseLiteralValue($ast, $variables),
        ]);
    }

    public static function parseLiteralValue($ast, ?array $variables = null): mixed
    {
        if ($ast instanceof VariableNode) {
            return $variables[$ast->name->value] ?? null;
        }

        if ($ast instanceof ObjectValueNode) {
            $obj = [];
            foreach ($ast->fields as $field) {
                $obj[$field->name->value] = self::parseLiteralValue($field->value, $variables);
            }

            return $obj;
        }

        if ($ast instanceof ListValueNode) {
            $list = [];
            foreach ($ast->values as $val) {
                $list[] = self::parseLiteralValue($val, $variables);
            }

            return $list;
        }

        if ($ast instanceof IntValueNode) {
            return (int) $ast->value;
        }

        if ($ast instanceof FloatValueNode) {
            return (float) $ast->value;
        }

        if ($ast instanceof BooleanValueNode) {
            return (bool) $ast->value;
        }

        if ($ast instanceof NullValueNode) {
            return null;
        }

        return property_exists($ast, 'value') ? $ast->value : null;
    }

    public static function nonNull(Type $type): NonNull
    {
        return Type::nonNull($type);
    }

    public static function listOf(Type $type): ListOfType
    {
        return Type::listOf($type);
    }

    public static function get(string $name, callable $resolver): Type
    {
        return self::$types[$name] ??= $resolver();
    }

    public static function pageInfo(): ObjectType
    {
        return self::$types['PageInfo'] ??= new ObjectType([
            'name' => 'PageInfo',
            'fields' => [
                'startCursor' => Type::string(),
                'endCursor' => Type::string(),
                'hasNextPage' => ['type' => Type::boolean(), 'resolve' => fn ($p) => (bool) ($p['hasNextPage'] ?? false)],
                'hasPreviousPage' => ['type' => Type::boolean(), 'resolve' => fn ($p) => (bool) ($p['hasPreviousPage'] ?? false)],
            ],
        ]);
    }

    public static function themeCustomizationTranslation(): ObjectType
    {
        return self::$types['ThemeCustomizationTranslation'] ??= new ObjectType([
            'name' => 'ThemeCustomizationTranslation',
            'fields' => [
                'id' => Type::id(),
                'themeCustomizationId' => ['type' => Type::id(), 'resolve' => fn ($t) => $t->theme_customization_id ?? ($t['theme_customization_id'] ?? null)],
                'locale' => ['type' => Type::string(), 'resolve' => fn ($t) => $t->locale ?? ($t['locale'] ?? null)],
                'options' => ['type' => self::json(), 'resolve' => fn ($t) => $t->options ?? ($t['options'] ?? null)],
            ],
        ]);
    }

    public static function themeCustomization(): ObjectType
    {
        return self::$types['ThemeCustomization'] ??= new ObjectType([
            'name' => 'ThemeCustomization',
            'fields' => [
                'id' => Type::id(),
                'type' => Type::string(),
                'name' => Type::string(),
                'status' => ['type' => Type::int(), 'resolve' => fn ($t) => (int) ($t->status ?? 1)],
                'sortOrder' => ['type' => Type::int(), 'resolve' => fn ($t) => (int) ($t->sort_order ?? 0)],
                'translations' => [
                    'type' => self::connection('ThemeCustomizationTranslation', self::themeCustomizationTranslation()),
                    'resolve' => function ($tc) {
                        $translations = $tc->translations ?? [];
                        $edges = collect($translations)->map(fn ($tr) => ['node' => $tr, 'cursor' => (string) ($tr->id ?? 1)])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
            ],
        ]);
    }

    public static function connection(string $name, ObjectType $nodeType): ObjectType
    {
        $connKey = $name.'Connection';
        if (isset(self::$types[$connKey])) {
            return self::$types[$connKey];
        }

        $edgeType = self::$types[$name.'Edge'] ??= new ObjectType([
            'name' => $name.'Edge',
            'fields' => [
                'cursor' => Type::string(),
                'node' => $nodeType,
            ],
        ]);

        return self::$types[$connKey] = new ObjectType([
            'name' => $connKey,
            'fields' => [
                'edges' => Type::listOf($edgeType),
                'totalCount' => Type::int(),
                'pageInfo' => [
                    'type' => self::pageInfo(),
                    'resolve' => fn ($root) => $root['pageInfo'] ?? [
                        'startCursor' => null,
                        'endCursor' => null,
                        'hasNextPage' => false,
                        'hasPreviousPage' => false,
                    ],
                ],
            ],
        ]);
    }

    public static function locale(): ObjectType
    {
        return self::$types['Locale'] ??= new ObjectType([
            'name' => 'Locale',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($l) => $l['id'] ?? $l->id],
                'code' => Type::string(),
                'name' => Type::string(),
                'direction' => Type::string(),
            ],
        ]);
    }

    public static function currency(): ObjectType
    {
        return self::$types['Currency'] ??= new ObjectType([
            'name' => 'Currency',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($c) => $c['id'] ?? $c->id],
                'code' => Type::string(),
                'name' => Type::string(),
                'symbol' => Type::string(),
            ],
        ]);
    }

    public static function channel(): ObjectType
    {
        return self::$types['Channel'] ??= new ObjectType([
            'name' => 'Channel',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($c) => $c['id'] ?? $c->id],
                'code' => Type::string(),
                'name' => Type::string(),
                'translation' => [
                    'type' => self::get('ChannelTranslation', fn () => new ObjectType([
                        'name' => 'ChannelTranslation',
                        'fields' => [
                            'name' => ['type' => Type::string(), 'resolve' => fn ($tr) => (string) (is_array($tr) ? ($tr['name'] ?? '') : ($tr->name ?? ''))],
                        ],
                    ])),
                    'resolve' => fn ($ch) => ['name' => (string) (is_array($ch) ? ($ch['name'] ?? 'Default Channel') : ($ch?->name ?? 'Default Channel'))],
                ],
                'hostname' => Type::string(),
                'theme' => Type::string(),
                'timezone' => ['type' => Type::string(), 'resolve' => fn ($c) => config('app.timezone', 'UTC')],
                'homeSeo' => ['type' => self::json(), 'resolve' => fn ($c) => $c['home_seo'] ?? $c->home_seo],
                'logoUrl' => ['type' => Type::string(), 'resolve' => fn ($c) => $c['logo_url'] ?? (method_exists($c, 'logo_url') ? $c->logo_url() : null)],
                'faviconUrl' => ['type' => Type::string(), 'resolve' => fn ($c) => $c['favicon_url'] ?? (method_exists($c, 'favicon_url') ? $c->favicon_url() : null)],
                'locales' => [
                    'type' => self::connection('Locale', self::locale()),
                    'resolve' => function ($c) {
                        $locales = is_array($c) ? ($c['locales'] ?? []) : $c->locales;
                        $edges = collect($locales)->map(fn ($l) => ['node' => $l, 'cursor' => (string) ($l['id'] ?? $l->id)])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'currencies' => [
                    'type' => self::connection('Currency', self::currency()),
                    'resolve' => function ($c) {
                        $currencies = is_array($c) ? ($c['currencies'] ?? []) : $c->currencies;
                        $edges = collect($currencies)->map(fn ($cur) => ['node' => $cur, 'cursor' => (string) ($cur['id'] ?? $cur->id)])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'defaultLocale' => ['type' => self::locale(), 'resolve' => fn ($c) => is_array($c) ? ($c['default_locale'] ?? null) : $c->default_locale],
                'baseCurrency' => ['type' => self::currency(), 'resolve' => fn ($c) => is_array($c) ? ($c['base_currency'] ?? null) : $c->base_currency],
            ],
        ]);
    }

    public static function country(): ObjectType
    {
        return self::$types['Country'] ??= new ObjectType([
            'name' => 'Country',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($c) => (int) $c->id],
                'code' => Type::string(),
                'name' => ['type' => Type::string(), 'resolve' => fn ($c) => $c->name ?? $c->code],
            ],
        ]);
    }

    public static function countryState(): ObjectType
    {
        return self::$types['CountryState'] ??= new ObjectType([
            'name' => 'CountryState',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($s) => (int) $s->id],
                'code' => Type::string(),
                'defaultName' => ['type' => Type::string(), 'resolve' => fn ($s) => $s->default_name ?? $s->code],
                'countryId' => ['type' => Type::int(), 'resolve' => fn ($s) => (int) $s->country_id],
                'countryCode' => ['type' => Type::string(), 'resolve' => fn ($s) => $s->country_code],
            ],
        ]);
    }
}
