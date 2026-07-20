<?php

namespace Tests\Unit\Fulfillment;

use PHPUnit\Framework\TestCase;
use Webkul\Fulfillment\Services\SecretRedactor;

class SecretRedactorTest extends TestCase
{
    /** Requirement 11.1 / 11.4 — value embedded in free text is scrubbed. */
    public function test_it_redacts_a_secret_value_embedded_in_a_message(): void
    {
        $message = 'Request failed using access_token=abc123XYZ then retried';

        $result = SecretRedactor::redactString($message, ['abc123XYZ']);

        $this->assertStringNotContainsString('abc123XYZ', $result);
        $this->assertStringContainsString(SecretRedactor::PLACEHOLDER, $result);
        $this->assertStringContainsString('Request failed', $result);
        $this->assertStringContainsString('then retried', $result);
    }

    /** Requirement 11.1 — secret-keyed array values are replaced by key. */
    public function test_it_redacts_values_stored_under_secret_keys(): void
    {
        $payload = [
            'access_token' => 'tok-1',
            'app_secret' => 'sec-2',
            'refresh_token' => 'ref-3',
            'method' => 'aliexpress.ds.order.create',
        ];

        $result = SecretRedactor::redact($payload);

        $this->assertSame(SecretRedactor::PLACEHOLDER, $result['access_token']);
        $this->assertSame(SecretRedactor::PLACEHOLDER, $result['app_secret']);
        $this->assertSame(SecretRedactor::PLACEHOLDER, $result['refresh_token']);
        $this->assertSame('aliexpress.ds.order.create', $result['method']);
    }

    /** Requirement 11.4 — a secret found under a key is also scrubbed from sibling text. */
    public function test_it_scrubs_secret_values_discovered_from_keys_across_the_payload(): void
    {
        $payload = [
            'access_token' => 'super-secret-token',
            'error' => 'call rejected: token super-secret-token is expired',
            'nested' => ['note' => 'retry with super-secret-token later'],
        ];

        $result = SecretRedactor::redact($payload);

        $this->assertSame(SecretRedactor::PLACEHOLDER, $result['access_token']);
        $this->assertStringNotContainsString('super-secret-token', $result['error']);
        $this->assertStringNotContainsString('super-secret-token', $result['nested']['note']);
    }

    /** Requirement 11.4 — recursion reaches deeply nested structures. */
    public function test_it_redacts_recursively(): void
    {
        $payload = [
            'body' => [
                'credentials' => [
                    'refresh_token' => 'deep-secret',
                ],
            ],
        ];

        $result = SecretRedactor::redact($payload);

        $this->assertSame(SecretRedactor::PLACEHOLDER, $result['body']['credentials']['refresh_token']);
    }

    /** Requirement 11.5 — content without any secret is returned unchanged. */
    public function test_it_leaves_content_without_secrets_unchanged(): void
    {
        $message = 'Order 1001 submitted successfully';
        $payload = ['order_id' => 1001, 'state' => 'submitted'];

        $this->assertSame($message, SecretRedactor::redactString($message, []));
        $this->assertSame($message, SecretRedactor::redact($message));
        $this->assertSame($payload, SecretRedactor::redact($payload));
    }

    /** Requirement 11.3 — messages within the limit are not altered. */
    public function test_it_does_not_truncate_short_messages(): void
    {
        $message = str_repeat('a', SecretRedactor::MAX_ERROR_LENGTH);

        $this->assertSame($message, SecretRedactor::truncate($message));
    }

    /** Requirement 11.3 — messages over the limit keep the first 2000 chars plus an indicator. */
    public function test_it_truncates_long_messages_with_an_indicator(): void
    {
        $message = str_repeat('b', SecretRedactor::MAX_ERROR_LENGTH + 500);

        $result = SecretRedactor::truncate($message);

        $this->assertStringStartsWith(str_repeat('b', SecretRedactor::MAX_ERROR_LENGTH), $result);
        $this->assertStringEndsWith(SecretRedactor::TRUNCATION_INDICATOR, $result);
        $this->assertSame(
            str_repeat('b', SecretRedactor::MAX_ERROR_LENGTH).SecretRedactor::TRUNCATION_INDICATOR,
            $result
        );
    }

    /** Requirement 5.9 — truncate accepts a custom limit (attempt messages use 1000). */
    public function test_it_truncates_to_a_custom_limit(): void
    {
        $message = str_repeat('c', 1500);

        $result = SecretRedactor::truncate($message, 1000);

        $this->assertSame(str_repeat('c', 1000).SecretRedactor::TRUNCATION_INDICATOR, $result);
    }

    /** Requirements 11.1 + 11.3 — sanitize both redacts and truncates. */
    public function test_sanitize_redacts_then_truncates(): void
    {
        $secret = 'my-secret-value';
        $message = 'prefix '.$secret.' '.str_repeat('d', SecretRedactor::MAX_ERROR_LENGTH);

        $result = SecretRedactor::sanitize($message, [$secret]);

        $this->assertStringNotContainsString($secret, $result);
        $this->assertStringEndsWith(SecretRedactor::TRUNCATION_INDICATOR, $result);
    }
}
