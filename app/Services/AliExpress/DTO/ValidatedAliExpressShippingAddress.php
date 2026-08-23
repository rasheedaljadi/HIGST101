<?php

namespace App\Services\AliExpress\DTO;

class ValidatedAliExpressShippingAddress
{
    public function __construct(
        public readonly string $contactPerson,
        public readonly string $phoneNum,
        public readonly string $mobileNo,
        public readonly string $phoneCountry,
        public readonly string $address,
        public readonly string $city,
        public readonly string $province,
        public readonly string $zip,
        public readonly string $country,
        public readonly ?string $companyName = null,
        public readonly ?string $passportNo = null,
        public readonly ?string $address2 = null
    ) {}

    /**
     * Convert to the canonical logistics_address payload expected by AliExpress API.
     *
     * @return array<string, string>
     */
    public function toLogisticsAddressArray(): array
    {
        $payload = [
            'contact_person' => $this->contactPerson,
            'full_name' => $this->contactPerson,
            'phone_num' => $this->phoneNum,
            'mobile_no' => $this->mobileNo,
            'phone_country' => $this->phoneCountry,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'zip' => $this->zip,
            'country' => $this->country,
            'company_name' => $this->companyName ?? $this->contactPerson,
        ];

        if ($this->country === 'SA') {
            $code = $this->passportNo ?: $this->zip;
            $payload['passport_no'] = $code;
            $payload['tax_number'] = $code;
            $payload['foreigner_passport_no'] = $code;
            $payload['national_address'] = $code;
            $payload['national_number'] = $code;
            $payload['short_address'] = $code;
            $payload['address2'] = $this->address2 ?: $code;
        } elseif (! empty($this->passportNo)) {
            $payload['passport_no'] = $this->passportNo;
        }

        if (! empty($this->address2) && ! isset($payload['address2'])) {
            $payload['address2'] = $this->address2;
        }

        return $payload;
    }

    /**
     * Return safe masked representation for logs, exceptions, and audit trails without PII.
     *
     * @return array{
     *     country: string,
     *     city: string,
     *     province: string,
     *     zip_masked: string,
     *     zip_length: int,
     *     is_sa: bool,
     *     is_valid: bool
     * }
     */
    public function getMaskedSummary(): array
    {
        $zipLen = strlen($this->zip);
        $zipMasked = ($zipLen >= 4)
            ? substr($this->zip, 0, 2).'****'.substr($this->zip, -2)
            : '****';

        return [
            'country' => $this->country,
            'city' => $this->city,
            'province' => $this->province,
            'zip_masked' => $zipMasked,
            'zip_length' => $zipLen,
            'is_sa' => ($this->country === 'SA'),
            'is_valid' => true,
        ];
    }

    /**
     * Prevent accidental PII disclosure in string contexts.
     */
    public function __toString(): string
    {
        return '[ValidatedAliExpressShippingAddress: country='.$this->country.', zip_len='.strlen($this->zip).']';
    }
}
