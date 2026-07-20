<?php

namespace Webkul\Fulfillment\DataObjects;

/**
 * Normalized result of `createSupplierOrder()` / `cancelSupplierOrder()`.
 *
 * A Fulfillment_Provider adapter translates the raw upstream response or error
 * into this shape (see ADR-002 and design section 5.4 Failure Handling). The
 * `isRetryable` flag distinguishes a Transient_Failure (`true`) from a
 * Permanent_Failure (`false`) so the FulfillmentService can decide whether to
 * retry with backoff or route to manual review.
 *
 * Plain, framework-agnostic, immutable value object.
 */
final class SupplierOrderResult
{
    /**
     * @param  bool  $ok  Whether the operation succeeded.
     * @param  string|null  $externalOrderId  Supplier order identifier, present (non-empty) on success.
     * @param  bool  $isRetryable  For failures: true = Transient_Failure, false = Permanent_Failure.
     * @param  string|null  $code  Raw provider status/error code, for auditing.
     * @param  string|null  $message  Human-readable message (secrets must be redacted upstream).
     * @param  array<mixed>|null  $raw  Normalized, secret-free snapshot of the provider payload.
     */
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $externalOrderId = null,
        public readonly bool $isRetryable = false,
        public readonly ?string $code = null,
        public readonly ?string $message = null,
        public readonly ?array $raw = null,
    ) {}

    /**
     * Named constructor for a successful result carrying the external order id.
     *
     * @param  array<mixed>|null  $raw
     */
    public static function success(string $externalOrderId, ?string $code = null, ?string $message = null, ?array $raw = null): self
    {
        return new self(
            ok: true,
            externalOrderId: $externalOrderId,
            isRetryable: false,
            code: $code,
            message: $message,
            raw: $raw,
        );
    }

    /**
     * Named constructor for a failed result, classified as transient or permanent.
     *
     * @param  array<mixed>|null  $raw
     */
    public static function failure(bool $isRetryable, ?string $code = null, ?string $message = null, ?array $raw = null): self
    {
        return new self(
            ok: false,
            externalOrderId: null,
            isRetryable: $isRetryable,
            code: $code,
            message: $message,
            raw: $raw,
        );
    }
}
