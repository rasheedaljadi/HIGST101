# Runbook: High Rate Limit Throttling

## Symptoms
- HTTP 429 status code or Rate Limit error code from AliExpress.
- Rapidly decreasing success rate in metrics.

## Recovery Steps
1. The circuit breaker and rate limit interceptor will automatically hold outgoing requests.
2. In config/fulfillment.php, adjust the rate limit threshold or delay values.
3. Queue workers will automatically backoff and retry later.
