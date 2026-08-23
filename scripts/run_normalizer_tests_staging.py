import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    runner_php = f"""<?php
$projDir = '{remote_base}';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Support\\AliExpressMoneyNormalizer;

$tests = [
    'test_normalizes_integer_cents_correctly' => function() {{
        $opt = ['service_name' => 'CAINIAO_STD', 'shipping_fee_cent' => 500, 'shipping_fee_currency' => 'USD'];
        $res = AliExpressMoneyNormalizer::normalizeFreightOption($opt);
        return $res['is_valid'] === true && $res['normalized_minor'] === 500 && $res['formatted_decimal'] === '5.00' && $res['raw_unit'] === 'minor_cents';
    }},
    'test_normalizes_decimal_string_in_cent_field_without_100x_error' => function() {{
        $opt = [
            'code' => 'CAINIAO_FULFILLMENT_STD',
            'company' => 'AliExpress Selection Standard',
            'shipping_fee_currency' => 'USD',
            'shipping_fee_cent' => '5.00',
            'shipping_fee_format' => 'US $5.00',
            'tracking' => true,
        ];
        $res = AliExpressMoneyNormalizer::normalizeFreightOption($opt);
        return $res['is_valid'] === true && $res['normalized_minor'] === 500 && $res['formatted_decimal'] === '5.00' && $res['raw_unit'] === 'decimal_major_despite_cent_name';
    }},
    'test_normalizes_standard_decimal_fee_correctly' => function() {{
        $opt = ['service_name' => 'CAINIAO_STD', 'shipping_fee' => '12.50', 'currency' => 'USD'];
        $res = AliExpressMoneyNormalizer::normalizeFreightOption($opt);
        return $res['is_valid'] === true && $res['normalized_minor'] === 1250 && $res['formatted_decimal'] === '12.50' && $res['raw_unit'] === 'decimal_standard';
    }},
    'test_normalizes_free_shipping_correctly' => function() {{
        $res1 = AliExpressMoneyNormalizer::normalizeFreightOption(['is_free' => true, 'currency' => 'USD']);
        $res2 = AliExpressMoneyNormalizer::normalizeFreightOption(['free_shipping' => true, 'currency' => 'USD']);
        return $res1['is_valid'] === true && $res1['normalized_minor'] === 0 && $res1['formatted_decimal'] === '0.00' && $res1['raw_unit'] === 'boolean_free' &&
               $res2['is_valid'] === true && $res2['normalized_minor'] === 0 && $res2['formatted_decimal'] === '0.00' && $res2['raw_unit'] === 'boolean_free';
    }},
    'test_rejects_conflicting_shipping_fee_fields' => function() {{
        $opt = ['shipping_fee_cent' => 500, 'shipping_fee' => '15.00', 'currency' => 'USD'];
        $res = AliExpressMoneyNormalizer::normalizeFreightOption($opt);
        return $res['is_valid'] === false && str_contains($res['error'], 'PRICE_OR_FREIGHT_AMOUNT_AMBIGUOUS');
    }},
    'test_rejects_missing_or_ambiguous_fields' => function() {{
        $opt = ['service_name' => 'UNKNOWN_CARRIER', 'currency' => 'USD'];
        $res = AliExpressMoneyNormalizer::normalizeFreightOption($opt);
        return $res['is_valid'] === false && $res['raw_unit'] === 'unknown' && str_contains($res['error'], 'PRICE_OR_FREIGHT_AMOUNT_AMBIGUOUS');
    }},
];

$results = ['total' => count($tests), 'passed' => 0, 'failed' => 0, 'details' => []];
foreach ($tests as $name => $fn) {{
    try {{
        $passed = $fn();
        if ($passed) {{
            $results['passed']++;
            $results['details'][$name] = 'PASS';
            echo "PASS: $name\n";
        }} else {{
            $results['failed']++;
            $results['details'][$name] = 'FAIL';
            echo "FAIL: $name\n";
        }}
    }} catch (\\Throwable $e) {{
        $results['failed']++;
        $results['details'][$name] = 'ERROR: ' . $e->getMessage();
        echo "ERROR: $name -> " . $e->getMessage() . "\n";
    }}
}}

echo json_encode($results, JSON_PRETTY_PRINT);
"""
    sftp = client.open_sftp()
    with sftp.file('/tmp/run_normalizer_unit_tests.php', 'w') as f:
        f.write(runner_php)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/run_normalizer_unit_tests.php && rm -f /tmp/run_normalizer_unit_tests.php")
    print("=== UNIT TEST EXECUTION ON STAGING ===")
    print(out)
    if err:
        print("ERR:", err)
        
    client.close()

if __name__ == '__main__':
    main()
