<?php

namespace Webkul\Fulfillment\Providers\AliExpress;

use App\Services\AliExpress\AliExpressApiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Fulfillment\Services\Domain\OutgoingRequestRegistry;

class AliExpressHttpClient extends AliExpressApiClient
{
    protected OutgoingRequestRegistry $outgoingRegistry;

    public function __construct(
        ?string $appKey = null,
        ?string $appSecret = null,
        ?OutgoingRequestRegistry $outgoingRegistry = null
    ) {
        parent::__construct($appKey, $appSecret);
        $this->outgoingRegistry = $outgoingRegistry ?? app(OutgoingRequestRegistry::class);
    }

    /**
     * Call AliExpress Dropshipping API with safety layers.
     */
    public function callResilient(
        string $method,
        string $accessToken,
        array $params,
        array $meta = []
    ): array {
        $correlationId = $meta['correlation_id'] ?? 'N/A';
        $causationId = $meta['causation_id'] ?? 'N/A';
        $traceId = $meta['trace_id'] ?? null;
        $spanId = $meta['span_id'] ?? null;
        $sessionId = $meta['procurement_session_id'] ?? null;
        $purchaseOrderId = $meta['purchase_order_id'] ?? null;
        $providerAccountId = $meta['provider_account_id'] ?? null;
        $idempotencyKey = $meta['idempotency_key'] ?? null;

        // Rate limiting check
        $this->checkRateLimit($method);

        // Circuit breaker check
        $this->checkCircuitBreaker();

        $requestPayload = array_merge($params, [
            'method'      => $method,
            'app_key'     => $this->appKey,
            'format'      => 'json',
            'sign_method' => config('aliexpress.sign_method', 'sha256'),
        ]);

        $requestJson = json_encode($requestPayload);
        $requestHash = hash('sha256', $requestJson);

        // Outgoing idempotency check
        if ($idempotencyKey) {
            $existing = $this->outgoingRegistry->findRequest($requestHash, $idempotencyKey);
            if ($existing && $existing->response_payload) {
                Log::channel('aliexpress')->info("OutgoingRequestRegistry match found. Bypassing duplicate API call.", [
                    'request_hash'    => $requestHash,
                    'idempotency_key' => $idempotencyKey,
                ]);

                return [
                    'ok'          => true,
                    'status'      => 200,
                    'code'        => $existing->response_hash,
                    'message'     => 'Restored from Idempotency registry',
                    'body'          => $existing->response_payload,
                    'from_cache'  => true
                ];
            }
        }

        $startTime = microtime(true);

        try {
            $result = parent::call($method, $accessToken, $params);
            $endTime = microtime(true);
            $latencyMs = ($endTime - $startTime) * 1000;

            // Save to archive log
            $this->logExternalApiCall(
                provider: 'aliexpress',
                endpoint: $method,
                method: 'POST',
                apiVersion: $meta['api_version'] ?? 'v2',
                providerApiVersion: $meta['provider_api_version'] ?? '2026-06',
                requestPayload: $requestPayload,
                responsePayload: $result['body'] ?? null,
                statusCode: $result['status'] ?? 200,
                latencyMs: $latencyMs,
                correlationId: $correlationId,
                causationId: $causationId,
                traceId: $traceId,
                spanId: $spanId,
                sessionId: $sessionId,
                purchaseOrderId: $purchaseOrderId,
                providerAccountId: $providerAccountId,
                errorMessage: $result['message'] ?? null
            );

            // Record in OutgoingRequestRegistry
            if ($idempotencyKey && $result['ok']) {
                $this->outgoingRegistry->recordRequest(
                    requestHash: $requestHash,
                    endpoint: $method,
                    idempotencyKey: $idempotencyKey,
                    responsePayload: $result['body'] ?? null,
                    responseHash: $result['code']
                );
            }

            return $result;

        } catch (\Throwable $e) {
            $endTime = microtime(true);
            $latencyMs = ($endTime - $startTime) * 1000;

            $this->logExternalApiCall(
                provider: 'aliexpress',
                endpoint: $method,
                method: 'POST',
                apiVersion: $meta['api_version'] ?? 'v2',
                providerApiVersion: $meta['provider_api_version'] ?? '2026-06',
                requestPayload: $requestPayload,
                responsePayload: null,
                statusCode: 500,
                latencyMs: $latencyMs,
                correlationId: $correlationId,
                causationId: $causationId,
                traceId: $traceId,
                spanId: $spanId,
                sessionId: $sessionId,
                purchaseOrderId: $purchaseOrderId,
                providerAccountId: $providerAccountId,
                errorMessage: $e->getMessage()
            );

            // Record circuit breaker failure count
            $this->recordFailure();

            throw $e;
        }
    }

    /**
     * Override base call method to route all API calls through the resilient safety layer.
     */
    public function call(string $method, string $accessToken, array $params = []): array
    {
        // Enrich meta parameters dynamically from params or context
        $meta = [];
        $outOrderId = null;
        
        if (isset($params['param_place_order_request4_open_api_d_t_o']['out_order_id'])) {
            $outOrderId = $params['param_place_order_request4_open_api_d_t_o']['out_order_id'];
        } elseif (isset($params['out_order_id'])) {
            $outOrderId = $params['out_order_id'];
        } elseif (isset($params['order_id'])) {
            $po = \Webkul\Fulfillment\Models\PurchaseOrder::where('external_order_id', $params['order_id'])->first();
            if ($po) {
                $outOrderId = $po->internal_reference;
            }
        } elseif (isset($params['aliexpress_order_id'])) {
            $po = \Webkul\Fulfillment\Models\PurchaseOrder::where('external_order_id', $params['aliexpress_order_id'])->first();
            if ($po) {
                $outOrderId = $po->internal_reference;
            }
        }

        if ($outOrderId) {
            $po = \Webkul\Fulfillment\Models\PurchaseOrder::where('internal_reference', $outOrderId)->first();
            if ($po) {
                $orderProcess = \Webkul\Fulfillment\Models\OrderProcess::where('order_id', $po->order_id)->first();
                $session = \Webkul\Fulfillment\Models\ProcurementSession::where('procurement_aggregate_id', function ($query) use ($po) {
                    $query->select('id')->from('procurement_aggregates')->where('purchase_order_id', $po->id);
                })->first();

                $meta = [
                    'correlation_id'        => $orderProcess?->correlation_id ?? $po->idempotency_key,
                    'causation_id'          => $po->idempotency_key,
                    'procurement_session_id'=> $session?->id,
                    'purchase_order_id'     => $po->id,
                    'provider_account_id'   => $po->provider_account_id,
                    'idempotency_key'       => $po->idempotency_key,
                    'api_version'           => 'v1',
                    'provider_api_version'  => '2026-06',
                ];
            }
        }

        return $this->callResilient($method, $accessToken, $params, $meta);
    }

    protected function checkRateLimit(string $method): void
    {
        $limitKey = "rate_limit:aliexpress:" . date('Y-m-d-H-i');
        $calls = (int) \Illuminate\Support\Facades\Cache::get($limitKey, 0);

        if ($calls > 1000) {
            throw new \RuntimeException("Rate limit exceeded for AliExpress API");
        }

        \Illuminate\Support\Facades\Cache::put($limitKey, $calls + 1, 60);
    }

    protected function checkCircuitBreaker(): void
    {
        $failures = (int) \Illuminate\Support\Facades\Cache::get("circuit_breaker:aliexpress:failures", 0);
        if ($failures >= 5) {
            throw new \RuntimeException("Circuit breaker open for AliExpress API");
        }
    }

    protected function recordFailure(): void
    {
        $failures = (int) \Illuminate\Support\Facades\Cache::get("circuit_breaker:aliexpress:failures", 0);
        \Illuminate\Support\Facades\Cache::put("circuit_breaker:aliexpress:failures", $failures + 1, 300);
    }

    protected function logExternalApiCall(
        string $provider,
        string $endpoint,
        string $method,
        ?string $apiVersion,
        ?string $providerApiVersion,
        ?array $requestPayload,
        ?array $responsePayload,
        ?int $statusCode,
        float $latencyMs,
        string $correlationId,
        string $causationId,
        ?string $traceId,
        ?string $spanId,
        ?int $sessionId,
        ?int $purchaseOrderId,
        ?int $providerAccountId,
        ?string $errorMessage
    ): void {
        DB::table('external_api_logs')->insert([
            'provider'             => $provider,
            'endpoint'             => $endpoint,
            'method'               => $method,
            'api_version'          => $apiVersion,
            'provider_api_version' => $providerApiVersion,
            'request_payload'      => json_encode($requestPayload),
            'response_payload'     => json_encode($responsePayload),
            'status_code'          => $statusCode,
            'latency_ms'           => $latencyMs,
            'correlation_id'       => $correlationId,
            'causation_id'         => $causationId,
            'trace_id'             => $traceId,
            'span_id'              => $spanId,
            'procurement_session_id'=> $sessionId,
            'purchase_order_id'    => $purchaseOrderId,
            'provider_account_id'  => $providerAccountId,
            'error_message'        => $errorMessage,
            'created_at'           => now(),
        ]);
    }
}
