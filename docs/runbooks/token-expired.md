# Runbook: Token Expired

## Symptoms
- 401 Unauthorized status returned from AliExpress API calls.
- ProviderHealthService status Degraded.

## Recovery Steps
1. The scheduler automatically runs `RefreshAccessTokenCommand` to acquire a new token.
2. If token refresh fails due to invalid/expired refresh token:
   - Perform manual re-authorization via OAuth dashboard.
   - Run `php artisan fulfillment:production-check` to verify the new token is active.
