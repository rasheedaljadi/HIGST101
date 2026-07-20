<?php

require __DIR__.'/src/Services/SecretRedactor.php';

use Webkul\Fulfillment\Services\SecretRedactor;

$failures = 0;
$assert = function (string $name, bool $cond) use (&$failures) {
    echo ($cond ? 'PASS' : 'FAIL').' - '.$name.PHP_EOL;
    if (! $cond) {
        $failures++;
    }
};

$placeholder = SecretRedactor::PLACEHOLDER;

// 11.1: placeholder shares NO character with a typical alphanumeric credential.
$secret = 'aXk92LmQ7Z0pR4tYbN1cVdE8sWfGhJ3';
$secretChars = array_unique(str_split($secret));
$placeholderChars = array_unique(str_split($placeholder));
$assert('11.1 placeholder disjoint from alphanumeric secret chars',
    array_intersect($secretChars, $placeholderChars) === []);

// 11.1 / 11.4: secret embedded in a string is replaced and no longer present.
$msg = "call failed access_token=$secret while posting order";
$red = SecretRedactor::redactString($msg, [$secret]);
$assert('11.4 secret removed from string', ! str_contains($red, $secret));
$assert('11.4 surrounding content preserved',
    str_contains($red, 'call failed access_token=') && str_contains($red, ' while posting order'));
$assert('11.1 placeholder inserted', str_contains($red, $placeholder));

// 11.1 / 11.4: array payload — secret-keyed values redacted and scrubbed from siblings.
$token = 'TOKEN_abc123DEF456';
$appSecret = 'SECRET_zzz999YYY000';
$refresh = 'REFRESH_qqq111WWW222';
$payload = [
    'access_token' => $token,
    'nested' => [
        'app_secret' => $appSecret,
        'note' => "used app_secret=$appSecret and token $token here",
        'refresh_token' => $refresh,
    ],
    'safe' => 'this is fine',
];
$out = SecretRedactor::redact($payload);
$flat = json_encode($out);
$assert('11.1 array access_token redacted', $out['access_token'] === $placeholder);
$assert('11.1 array app_secret redacted', $out['nested']['app_secret'] === $placeholder);
$assert('11.1 array refresh_token redacted', $out['nested']['refresh_token'] === $placeholder);
$assert('11.4 no secret leaks anywhere in payload',
    ! str_contains($flat, $token) && ! str_contains($flat, $appSecret) && ! str_contains($flat, $refresh));
$assert('11.4 sibling text secrets scrubbed', ! str_contains($out['nested']['note'], $token) && ! str_contains($out['nested']['note'], $appSecret));
$assert('11.5 non-secret content preserved', $out['safe'] === 'this is fine');

// 11.5: no secret present -> content unchanged.
$plain = 'a totally ordinary error message';
$assert('11.5 string unchanged when no secret', SecretRedactor::redactString($plain, []) === $plain);
$assert('11.5 array unchanged when no secret', SecretRedactor::redact(['a' => 'b', 'c' => ['d' => 'e']]) === ['a' => 'b', 'c' => ['d' => 'e']]);

// 11.3: truncation to 2000 chars with indicator, short messages untouched.
$short = str_repeat('x', 2000);
$assert('11.3 exactly 2000 not truncated', SecretRedactor::truncate($short) === $short);
$long = str_repeat('y', 2500);
$truncated = SecretRedactor::truncate($long);
$assert('11.3 keeps first 2000 chars', str_starts_with($truncated, str_repeat('y', 2000)));
$assert('11.3 appends truncation indicator', str_ends_with($truncated, SecretRedactor::TRUNCATION_INDICATOR));
$assert('11.3 no more than 2000 original chars retained',
    mb_substr($truncated, 0, 2000) === str_repeat('y', 2000) && mb_substr($truncated, 2000, mb_strlen(SecretRedactor::TRUNCATION_INDICATOR)) === SecretRedactor::TRUNCATION_INDICATOR);

// sanitize: redact THEN truncate (no secret fragment survives truncation).
$bigSecret = str_repeat('S', 50);
$sanitized = SecretRedactor::sanitize("prefix $bigSecret suffix", [$bigSecret], 30);
$assert('sanitize removes secret before truncation', ! str_contains($sanitized, $bigSecret));

echo PHP_EOL.($failures === 0 ? 'ALL PASSED' : "$failures FAILED").PHP_EOL;
exit($failures === 0 ? 0 : 1);
