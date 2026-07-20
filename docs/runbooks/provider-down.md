# Runbook: Provider Down Recovery

## Symptoms
- Timeout errors logged in `external_api_logs`.
- Circuit Breaker status open in health service.
- Outbox events processing slow down.

## Diagnostic Steps
1. Verify if the upstream AliExpress API status is down.
2. Run `php artisan fulfillment:smoke-test` to inspect connection health.

## Recovery Steps
1. The system automatically switches to retry with backoff for transient failures.
2. If down permanently for more than 4 hours:
   - Run the compensation saga if needed to release allocation or hold orders.
   - Inform the customer service.
