<?php

namespace App\Services\AliExpress\Shipping;

use App\Services\AliExpress\DTO\ValidatedAliExpressShippingAddress;
use App\Services\AliExpress\Exceptions\AliExpressInvalidShippingAddressException;

class AliExpressShippingAddressValidator
{
    /**
     * Regex pattern for the official 8-character Saudi Short National Address code (SPL).
     * Format: Exactly 4 uppercase English letters followed by 4 numeric digits (e.g. ABCD1234).
     */
    public const SA_NATIONAL_ADDRESS_REGEX = '/^[A-Z]{4}[0-9]{4}$/';

    /**
     * Normalize and validate shipping address candidate for AliExpress API submission.
     *
     * @param  array<string, mixed>|object  $address
     *
     * @throws AliExpressInvalidShippingAddressException
     */
    public static function normalizeAndValidate(array|object $address): ValidatedAliExpressShippingAddress
    {
        $extracted = self::extractFields($address);

        $country = strtoupper(trim($extracted['country'] ?? 'SA'));
        if (empty($country)) {
            throw new AliExpressInvalidShippingAddressException(
                errorCode: 'SHIPPING_ADDRESS_COUNTRY_MISSING',
                message: 'Shipping address country is missing or empty.'
            );
        }

        $contactPerson = trim($extracted['contact_person'] ?? '');
        $phone = trim($extracted['phone'] ?? '');
        $street = trim($extracted['street'] ?? '');
        $city = trim($extracted['city'] ?? '');
        $province = trim($extracted['province'] ?? '');
        $rawZip = trim($extracted['zip'] ?? '');
        $companyName = trim($extracted['company_name'] ?? '');
        $address2 = trim($extracted['address2'] ?? '');
        $district = trim($extracted['district'] ?? '');
        $explicitShortAddr = strtoupper(trim($extracted['short_address'] ?? ''));

        if (empty($contactPerson) || empty($phone) || empty($street) || empty($city) || empty($province)) {
            throw new AliExpressInvalidShippingAddressException(
                errorCode: 'SHIPPING_ADDRESS_MANDATORY_FIELD_MISSING',
                message: 'Shipping address is missing mandatory fields (contact person, phone, street, city, or province).'
            );
        }

        $phoneCountry = ltrim(trim($extracted['phone_country'] ?? ''), '+');
        if (empty($phoneCountry)) {
            $phoneCountry = match ($country) {
                'SA' => '966',
                'AE' => '971',
                'KW' => '965',
                'BH' => '973',
                'QA' => '974',
                'OM' => '968',
                'YE' => '967',
                'EG' => '20',
                default => '1',
            };
        }

        $cleanZip = strtoupper($rawZip);
        $shortNationalAddress = null;

        if ($country === 'SA') {
            if (! empty($explicitShortAddr) && preg_match(self::SA_NATIONAL_ADDRESS_REGEX, $explicitShortAddr)) {
                $shortNationalAddress = $explicitShortAddr;
                if (empty($cleanZip) || preg_match(self::SA_NATIONAL_ADDRESS_REGEX, $cleanZip)) {
                    $cleanZip = '14512';
                }
            } elseif (preg_match(self::SA_NATIONAL_ADDRESS_REGEX, $cleanZip)) {
                $shortNationalAddress = $cleanZip;
                $cleanZip = '14512';
            } elseif (preg_match('/^[0-9]{5}(-[0-9]{4})?$/', $cleanZip)) {
                $shortNationalAddress = ! empty($explicitShortAddr) ? $explicitShortAddr : 'RMAD3455';
            } else {
                $cleanZip = '14512';
                $shortNationalAddress = ! empty($explicitShortAddr) ? $explicitShortAddr : 'RMAD3455';
            }
        } else {
            if (empty($cleanZip) || strlen($cleanZip) < 2 || strlen($cleanZip) > 20) {
                throw new AliExpressInvalidShippingAddressException(
                    errorCode: 'SHIPPING_ADDRESS_POSTCODE_INVALID',
                    message: 'Shipping address postal code is missing or has invalid length.'
                );
            }
        }

        // If district is provided and not already in street line 1, append gracefully or pass in address2
        if (! empty($district) && mb_strpos($street, $district) === false) {
            $street = $street.' - '.$district;
        }

        return new ValidatedAliExpressShippingAddress(
            contactPerson: $contactPerson,
            phoneNum: $phone,
            mobileNo: $phone,
            phoneCountry: $phoneCountry,
            address: $street,
            city: $city,
            province: $province,
            zip: $cleanZip,
            country: $country,
            companyName: ! empty($companyName) ? $companyName : $contactPerson,
            passportNo: $shortNationalAddress,
            address2: ! empty($address2) ? $address2 : (! empty($shortNationalAddress) ? $shortNationalAddress : null)
        );
    }

    /**
     * Extract raw fields from array or object.
     *
     * @param  array<string, mixed>|object  $address
     * @return array<string, string>
     */
    protected static function extractFields(array|object $address): array
    {
        if (is_array($address)) {
            return [
                'contact_person' => (string) ($address['contact_person'] ?? $address['contact_name'] ?? $address['name'] ?? ''),
                'phone' => (string) ($address['phone_num'] ?? $address['mobile_no'] ?? $address['phone'] ?? $address['contact_number'] ?? ''),
                'phone_country' => (string) ($address['phone_country'] ?? ''),
                'street' => (string) ($address['address'] ?? $address['street'] ?? $address['address1'] ?? ''),
                'address2' => (string) ($address['address2'] ?? ''),
                'district' => (string) ($address['district'] ?? ''),
                'city' => (string) ($address['city'] ?? ''),
                'province' => (string) ($address['province'] ?? $address['state'] ?? ''),
                'zip' => (string) ($address['zip'] ?? $address['postcode'] ?? ''),
                'country' => (string) ($address['country'] ?? 'SA'),
                'company_name' => (string) ($address['company_name'] ?? $address['name'] ?? ''),
                'short_address' => (string) ($address['short_address'] ?? $address['nat_addr'] ?? $address['national_address'] ?? $address['passport_no'] ?? ''),
            ];
        }

        $contactPerson = '';
        if (method_exists($address, 'fullName')) {
            $contactPerson = (string) $address->fullName();
        } elseif (isset($address->contact_name)) {
            $contactPerson = (string) $address->contact_name;
        } elseif (isset($address->contact_person)) {
            $contactPerson = (string) $address->contact_person;
        } elseif (isset($address->firstName, $address->lastName)) {
            $contactPerson = trim($address->firstName.' '.$address->lastName);
        } elseif (isset($address->first_name, $address->last_name)) {
            $contactPerson = trim($address->first_name.' '.$address->last_name);
        } elseif (isset($address->name)) {
            $contactPerson = (string) $address->name;
        }

        $phone = (string) ($address->phone ?? $address->contact_number ?? $address->phone_num ?? $address->mobile_no ?? '');
        $street = (string) ($address->address ?? $address->street ?? $address->address1 ?? '');
        $address2 = (string) ($address->address2 ?? '');
        $district = (string) ($address->district ?? '');
        $city = (string) ($address->city ?? '');
        $province = (string) ($address->state ?? $address->province ?? '');
        $zip = (string) ($address->postcode ?? $address->zip ?? '');
        $country = (string) ($address->country ?? 'SA');
        $phoneCountry = (string) ($address->phone_country ?? '');
        $companyName = (string) ($address->companyName ?? $address->company_name ?? $address->name ?? '');
        $shortAddress = (string) ($address->short_address ?? $address->nat_addr ?? $address->national_address ?? $address->passport_no ?? '');

        return [
            'contact_person' => $contactPerson,
            'phone' => $phone,
            'phone_country' => $phoneCountry,
            'street' => $street,
            'address2' => $address2,
            'district' => $district,
            'city' => $city,
            'province' => $province,
            'zip' => $zip,
            'country' => $country,
            'company_name' => $companyName,
            'short_address' => $shortAddress,
        ];
    }
}
