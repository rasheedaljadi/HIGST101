<?php

namespace Webkul\MobileApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Webkul\MobileApi\Repositories\MobileApiKeyRepository;

class ValidateStorefrontKey
{
    public function __construct(
        protected MobileApiKeyRepository $mobileApiKeyRepository
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-STOREFRONT-KEY');

        if (! $key) {
            return response()->json([
                'errors' => [
                    [
                        'message' => 'X-STOREFRONT-KEY header is missing or empty.',
                        'extensions' => ['code' => 'UNAUTHORIZED'],
                    ],
                ],
            ], 401);
        }

        $apiKey = $this->mobileApiKeyRepository->findValidKey($key);

        if (! $apiKey) {
            return response()->json([
                'errors' => [
                    [
                        'message' => 'Invalid or inactive X-STOREFRONT-KEY provided.',
                        'extensions' => ['code' => 'UNAUTHORIZED'],
                    ],
                ],
            ], 401);
        }

        // Update last used timestamp asynchronously or directly
        $apiKey->update(['last_used_at' => now()]);

        // Attach verified apiKey to request attributes
        $request->attributes->set('mobile_api_key', $apiKey);

        // Configure context locale and currency if provided
        if ($locale = $request->header('X-LOCALE') ?: $request->header('x-locale')) {
            app()->setLocale($locale);
        }

        $currency = $request->header('X-CURRENCY')
            ?: $request->header('x-currency')
            ?: $request->get('currency');

        if ($currency) {
            $currency = strtoupper(trim($currency));
            $availableCurrencies = core()->getCurrentChannel()->currencies->pluck('code')->toArray();
            if (in_array($currency, $availableCurrencies)) {
                core()->setCurrentCurrency($currency);
                session()->put('currency', $currency);
            }
        }

        return $next($request);
    }
}
