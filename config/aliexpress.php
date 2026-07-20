<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AliExpress Open Platform Credentials
    |--------------------------------------------------------------------------
    |
    | These credentials are issued by the AliExpress Open Platform when you
    | register an application at https://openservice.aliexpress.com. Keep the
    | "app_secret" private and never expose it on the client side.
    |
    */

    'app_key' => env('ALIEXPRESS_APP_KEY'),

    'app_secret' => env('ALIEXPRESS_APP_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Callback (Redirect) URL
    |--------------------------------------------------------------------------
    |
    | AliExpress ONLY accepts HTTPS callback URLs (localhost / http are
    | rejected). This value MUST match — byte for byte — the "Callback URL"
    | registered in the AliExpress Open Platform console.
    |
    | Leave it null to let the application build it automatically from the
    | named route "aliexpress.oauth.callback" (forced to HTTPS). For local
    | development, set it to your public tunnel URL, e.g.:
    |
    |   ALIEXPRESS_REDIRECT_URI=https://your-subdomain.ngrok-free.app/aliexpress/callback
    |
    */

    'redirect_uri' => env('ALIEXPRESS_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | API Gateway Endpoints
    |--------------------------------------------------------------------------
    |
    | "authorize_url" is where the merchant is sent to grant access.
    | "token_url" is the system REST endpoint used to exchange the
    | authorization "code" for an access token. Both are HTTPS.
    |
    */

    'authorize_url' => env('ALIEXPRESS_AUTHORIZE_URL', 'https://api-sg.aliexpress.com/oauth/authorize'),

    'token_url' => env('ALIEXPRESS_TOKEN_URL', 'https://api-sg.aliexpress.com/rest'),

    /*
    | Business / system API gateway (TOP style). Dropshipping methods such as
    | aliexpress.ds.product.get are called here with a "method" parameter.
    | Unlike the /rest token gateway, its signature does NOT include a path.
    */

    'business_url' => env('ALIEXPRESS_BUSINESS_URL', 'https://api-sg.aliexpress.com/sync'),

    /*
    | The system API path used to create / refresh tokens. Used both as the
    | request path and as the prefix of the signature base string (IOP rule).
    */

    'token_create_path' => '/auth/token/create',

    'token_refresh_path' => '/auth/token/refresh',

    /*
    |--------------------------------------------------------------------------
    | Signature Method
    |--------------------------------------------------------------------------
    |
    | AliExpress (IOP gateway) supports "sha256" (HMAC-SHA256) and "md5".
    | sha256 is recommended.
    |
    */

    'sign_method' => env('ALIEXPRESS_SIGN_METHOD', 'sha256'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('ALIEXPRESS_HTTP_TIMEOUT', 60),

    'connect_timeout' => (int) env('ALIEXPRESS_HTTP_CONNECT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Product Import Defaults
    |--------------------------------------------------------------------------
    |
    | Defaults used when importing products via aliexpress.ds.product.get and
    | when mapping AliExpress data into the Bagisto catalog. "ship_to_country",
    | "target_currency", and "target_language" are passed to the product API so
    | prices/translations come back in the expected form. "attribute_code_prefix"
    | is prepended to auto-created variant attribute codes (e.g. "ae_color") to
    | avoid clashing with Bagisto core attributes.
    |
    */

    'import' => [

        'ship_to_country' => env('ALIEXPRESS_SHIP_TO_COUNTRY', 'SA'),

        'target_currency' => env('ALIEXPRESS_TARGET_CURRENCY', 'USD'),

        // The PRIMARY language used to fetch a product and derive its structure
        // (axes/variants). English keeps axis names like "Color"/"Size" stable
        // and matchable against the offline attribute dictionary.
        'primary_language' => env('ALIEXPRESS_PRIMARY_LANGUAGE', 'en'),

        // Kept for backward compatibility; primary_language is preferred.
        'target_language' => env('ALIEXPRESS_TARGET_LANGUAGE', 'en'),

        // Maps a Bagisto locale (language part) to the AliExpress
        // `target_language` value used to fetch that locale's display text.
        // Locales absent from this map fall back to the primary language.
        'language_map' => [
            'ar' => 'ar',
            'en' => 'en',
        ],

        'attribute_code_prefix' => env('ALIEXPRESS_ATTRIBUTE_CODE_PREFIX', 'ae_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Sync
    |--------------------------------------------------------------------------
    |
    | "category.method" is the AliExpress API used to fetch the category tree.
    | Category names are translated to Arabic from a static, offline dictionary
    | (App\Services\AliExpress\AliExpressCategoryDictionary) — no external AI is
    | involved anywhere in the import pipeline. Product display text comes
    | straight from AliExpress in each store language (see "import.language_map").
    |
    */

    'category' => [
        'method' => env('ALIEXPRESS_CATEGORY_METHOD', 'aliexpress.ds.category.get'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deferred Indexing (Pilot Feature)
    |--------------------------------------------------------------------------
    |
    | When set to true, reindexing (flat, price, and inventory) is deferred
    | during bulk AliExpress sync operations and processed via scheduled tasks
    | to prevent database lockups and high I/O overhead.
    |
    */

    'defer_indexing' => env('ALIEXPRESS_DEFER_INDEXING', false),
];
