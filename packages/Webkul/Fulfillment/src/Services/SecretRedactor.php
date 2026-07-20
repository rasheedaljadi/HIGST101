<?php

namespace Webkul\Fulfillment\Services;

use Illuminate\Support\Facades\Log;

/**
 * Removes provider credentials from any content before it is logged or
 * persisted, and bounds the size of stored error messages.
 *
 * The redaction and truncation helpers are pure functions so they can be unit
 * tested without the framework. Only {@see self::logFailure()} touches Laravel.
 *
 * Satisfies Requirement 11 (Log and Secret Safety):
 *  - 11.1 replace access_token / app_secret / refresh_token with a fixed
 *         placeholder that shares none of the secret's characters.
 *  - 11.2 write fulfillment failures to the existing "aliexpress" log channel.
 *  - 11.3 truncate stored error messages to a maximum length with an indicator.
 *  - 11.4 redact secret values embedded anywhere in a message or serialized
 *         payload while leaving the surrounding non-secret content unchanged.
 *  - 11.5 when no secret is present, content is persisted verbatim.
 */
final class SecretRedactor
{
    /**
     * The fixed placeholder inserted in place of a secret value.
     *
     * It is composed exclusively of non-alphanumeric symbols. AliExpress
     * credentials (access_token / app_secret / refresh_token) are long random
     * alphanumeric strings, so a symbol-only placeholder is guaranteed to share
     * none of the characters of the original secret value (Requirement 11.1).
     * The fixed length also avoids leaking the length of the redacted secret.
     */
    public const PLACEHOLDER = '[********]';

    /**
     * Maximum length of a stored error message before truncation (Requirement 11.3).
     */
    public const MAX_ERROR_LENGTH = 2000;

    /**
     * Appended to a message when the original exceeds {@see self::MAX_ERROR_LENGTH}.
     */
    public const TRUNCATION_INDICATOR = '…[truncated]';

    /**
     * Array keys whose values are always treated as secrets, regardless of the
     * value itself. Their embedded values are also scrubbed from sibling text.
     */
    public const SECRET_KEYS = ['access_token', 'app_secret', 'refresh_token'];

    /**
     * Recursively redact secrets from a string or an (array) payload.
     *
     * String input is scanned for the supplied secret values. Array input is
     * walked recursively: any element under a {@see self::SECRET_KEYS} key is
     * replaced with the placeholder, and every string element additionally has
     * the collected secret values scrubbed from it. When no secret is present
     * the content is returned unchanged (Requirement 11.5).
     *
     * @param  mixed  $content  A string, array, or scalar to sanitize.
     * @param  array<int, string>  $secrets  Extra known secret values to scrub.
     * @return mixed The same shape as $content with secrets removed.
     */
    public static function redact(mixed $content, array $secrets = [])
    {
        $secrets = self::collectSecrets($content, $secrets);

        return self::apply($content, $secrets);
    }

    /**
     * Redact the given secret values from a single string.
     *
     * @param  array<int, string>  $secrets
     */
    public static function redactString(string $text, array $secrets = []): string
    {
        $secrets = self::normalizeSecrets($secrets);

        if ($secrets === []) {
            return $text;
        }

        return str_replace($secrets, self::PLACEHOLDER, $text);
    }

    /**
     * Truncate a message to at most $max characters of original content,
     * appending a truncation indicator when the original exceeds the limit
     * (Requirement 11.3). Shorter messages are returned unchanged.
     */
    public static function truncate(string $message, int $max = self::MAX_ERROR_LENGTH): string
    {
        if ($max < 0) {
            $max = 0;
        }

        if (mb_strlen($message) <= $max) {
            return $message;
        }

        return mb_substr($message, 0, $max).self::TRUNCATION_INDICATOR;
    }

    /**
     * Redact secrets and then truncate — the canonical transform to apply
     * before persisting `last_error` or `fulfillment_attempts.message`.
     *
     * @param  array<int, string>  $secrets
     */
    public static function sanitize(string $message, array $secrets = [], int $max = self::MAX_ERROR_LENGTH): string
    {
        return self::truncate(self::redactString($message, $secrets), $max);
    }

    /**
     * Write a redacted fulfillment failure entry to the configured log channel
     * (defaults to the existing "aliexpress" channel — Requirement 11.2).
     *
     * @param  array<string, mixed>  $context
     * @param  array<int, string>  $secrets
     */
    public static function logFailure(string $message, array $context = [], array $secrets = []): void
    {
        $channel = config('fulfillment.log_channel', 'aliexpress');

        Log::channel($channel)->error(
            self::truncate(self::redactString($message, $secrets)),
            self::redact($context, $secrets)
        );
    }

    /**
     * Recursively replace secret-keyed values and scrub secret substrings.
     *
     * @param  array<int, string>  $secrets
     */
    private static function apply(mixed $content, array $secrets)
    {
        if (is_array($content)) {
            $result = [];

            foreach ($content as $key => $value) {
                if (is_string($key) && in_array($key, self::SECRET_KEYS, true)) {
                    $result[$key] = self::PLACEHOLDER;

                    continue;
                }

                $result[$key] = self::apply($value, $secrets);
            }

            return $result;
        }

        if (is_string($content)) {
            return $secrets === [] ? $content : str_replace($secrets, self::PLACEHOLDER, $content);
        }

        return $content;
    }

    /**
     * Merge caller-supplied secrets with any values discovered under a
     * secret key inside the content, so embedded occurrences are also scrubbed.
     *
     * @param  array<int, string>  $secrets
     * @return array<int, string>
     */
    private static function collectSecrets(mixed $content, array $secrets): array
    {
        $discovered = [];

        self::gather($content, $discovered);

        return self::normalizeSecrets(array_merge($secrets, $discovered));
    }

    /**
     * Walk an array collecting the string values stored under secret keys.
     *
     * @param  array<int, string>  $out
     */
    private static function gather(mixed $content, array &$out): void
    {
        if (! is_array($content)) {
            return;
        }

        foreach ($content as $key => $value) {
            if (is_string($key) && in_array($key, self::SECRET_KEYS, true) && is_scalar($value)) {
                $out[] = (string) $value;

                continue;
            }

            self::gather($value, $out);
        }
    }

    /**
     * Drop empty values and de-duplicate, longest first so overlapping secrets
     * are replaced greedily rather than leaving fragments behind.
     *
     * @param  array<int, mixed>  $secrets
     * @return array<int, string>
     */
    private static function normalizeSecrets(array $secrets): array
    {
        $normalized = [];

        foreach ($secrets as $secret) {
            if (! is_scalar($secret)) {
                continue;
            }

            $secret = (string) $secret;

            if ($secret !== '') {
                $normalized[$secret] = $secret;
            }
        }

        $normalized = array_values($normalized);

        usort($normalized, static fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $normalized;
    }
}
