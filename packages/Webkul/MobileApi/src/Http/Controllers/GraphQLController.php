<?php

namespace Webkul\MobileApi\Http\Controllers;

use App\Http\Controllers\Controller;
use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Webkul\Customer\Models\Customer;
use Webkul\MobileApi\GraphQL\Context;
use Webkul\MobileApi\GraphQL\Schema;

class GraphQLController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $query = $request->input('query');
        $variables = $request->input('variables');

        if (! $query && $request->getContent()) {
            $content = json_decode($request->getContent(), true);
            if (is_array($content)) {
                $query = $content['query'] ?? null;
                $variables = $content['variables'] ?? $variables;
            } else {
                $query = $request->getContent();
            }
        }

        if (is_string($variables)) {
            $variables = json_decode($variables, true);
        }

        if (! $query) {
            return response()->json([
                'errors' => [
                    ['message' => 'GraphQL query is required.'],
                ],
            ], 400);
        }

        // Customer authentication via Sanctum, bearer token, or session
        $customer = null;
        try {
            $bearerToken = $request->bearerToken();
            if ($bearerToken) {
                if (class_exists(PersonalAccessToken::class)) {
                    $tokenModel = PersonalAccessToken::findToken($bearerToken);
                    if ($tokenModel && $tokenModel->tokenable) {
                        $customer = $tokenModel->tokenable;
                    }
                }
                if (! $customer && class_exists(Customer::class)) {
                    $customer = Customer::where('api_token', $bearerToken)->first();
                }
            }

            if (! $customer && auth()->guard('sanctum')->check()) {
                $customer = auth()->guard('sanctum')->user();
            } elseif (! $customer && auth()->guard('customer')->check()) {
                $customer = auth()->guard('customer')->user();
            }

            if ($customer && auth()->guard('customer')) {
                auth()->guard('customer')->setUser($customer);
                \Webkul\Checkout\Facades\Cart::initCart($customer);
            }
        } catch (\Throwable $e) {
            $customer = null;
        }

        // Restore guest cart if token or header provided
        if (! $customer) {
            $cartId = $request->header('X-CART-ID') ?? $request->header('X-Cart-Id');
            if (! $cartId && $bearerToken) {
                $cartId = \Illuminate\Support\Facades\Cache::get("mobile_guest_cart_{$bearerToken}");
            }
            if ($cartId) {
                $existingCart = app(\Webkul\Checkout\Repositories\CartRepository::class)->find($cartId);
                if ($existingCart && $existingCart->is_active) {
                    \Webkul\Checkout\Facades\Cart::setCart($existingCart);
                }
            }
        }

        $context = new Context(
            request: $request,
            customer: $customer
        );

        $schema = (new Schema)->build();

        $result = GraphQL::executeQuery(
            schema: $schema,
            source: $query,
            rootValue: null,
            contextValue: $context,
            variableValues: $variables
        );

        // Persist guest cart to cache for subsequent calls
        $activeCart = \Webkul\Checkout\Facades\Cart::getCart();
        if ($activeCart && ! $customer && $bearerToken) {
            \Illuminate\Support\Facades\Cache::put("mobile_guest_cart_{$bearerToken}", $activeCart->id, now()->addDays(30));
        }

        if (! empty($result->errors)) {
            foreach ($result->errors as $error) {
                \Illuminate\Support\Facades\Log::error('[MobileApi GraphQL Error] '.$error->getMessage(), [
                    'previous' => $error->getPrevious()?->getMessage(),
                    'trace' => $error->getPrevious()?->getTraceAsString(),
                    'query' => $query,
                    'variables' => $variables,
                ]);
            }
        }

        $result->setErrorFormatter(function (\GraphQL\Error\Error $error) {
            $prev = $error->getPrevious();
            $msg = $prev ? $prev->getMessage() : $error->getMessage();

            return [
                'message' => $msg,
                'locations' => $error->getLocations(),
                'path' => $error->getPath(),
            ];
        });

        $debug = config('app.debug')
            ? (DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE)
            : DebugFlag::INCLUDE_DEBUG_MESSAGE;

        return response()->json($result->toArray($debug));
    }
}
