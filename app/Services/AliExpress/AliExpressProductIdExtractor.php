<?php

namespace App\Services\AliExpress;

use App\Exceptions\AliExpress\AliExpressImportException;

/**
 * Derives an AliExpress product id from raw input or a pasted product URL.
 *
 * Accepts either a bare numeric id or a full product URL in any of the
 * common AliExpress shapes and returns the numeric id as a string.
 */
class AliExpressProductIdExtractor
{
    /**
     * URL path patterns that contain the product id as the first capture group.
     *
     * @var string[]
     */
    protected array $patterns = [
        '#/item/(\d+)\.html#i',
        '#/i/(\d+)\.html#i',
        '#/product/(\d+)#i',
        '#(\d{6,})#',
    ];

    /**
     * Query parameter names that may carry the product id.
     *
     * @var string[]
     */
    protected array $queryKeys = ['productId', 'product_id'];

    /**
     * Extract the numeric AliExpress product id from the given input.
     *
     * @throws AliExpressImportException when the input is empty or no id can be derived.
     */
    public function extract(string $input): string
    {
        $input = trim($input);

        if ($input === '') {
            throw new AliExpressImportException(
                'An AliExpress product id or product URL is required.'
            );
        }

        // Pure-digit input is treated as the id directly (Requirement 2.1).
        if (ctype_digit($input)) {
            return $input;
        }

        // Check known query parameters first (Requirement 2.2).
        $query = parse_url($input, PHP_URL_QUERY);

        if (is_string($query) && $query !== '') {
            parse_str($query, $params);

            foreach ($this->queryKeys as $key) {
                if (isset($params[$key]) && is_string($params[$key]) && ctype_digit($params[$key])) {
                    return $params[$key];
                }
            }
        }

        // Search the path (falling back to the full input) for a known pattern.
        $path = parse_url($input, PHP_URL_PATH);
        $haystack = is_string($path) && $path !== '' ? $path : $input;

        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $haystack, $matches)) {
                return $matches[1];
            }
        }

        throw new AliExpressImportException(
            "Could not extract an AliExpress product id from the provided input: {$input}",
            ['input' => $input]
        );
    }
}
