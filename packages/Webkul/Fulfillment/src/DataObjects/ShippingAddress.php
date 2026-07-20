<?php

namespace Webkul\Fulfillment\DataObjects;

/**
 * Provider-agnostic shipping address.
 *
 * Sourced from the Customer_Order shipping address (`Webkul\Sales\Models\Order::shipping_address`,
 * see design section 5.8 Data Flow). A Fulfillment_Provider adapter maps these
 * neutral fields onto the shape the upstream provider expects.
 *
 * Field names mirror the Bagisto `addresses` table (Bagisto 2.x uses a single
 * `address` column that may contain multiple newline-separated lines). `country`
 * is a country code (e.g. "US").
 *
 * Plain, framework-agnostic, immutable value object.
 */
final class ShippingAddress
{
    /**
     * @param  string  $firstName  Recipient first name.
     * @param  string  $lastName  Recipient last name.
     * @param  string  $address  Street address (may contain newline-separated lines).
     * @param  string  $city  City.
     * @param  string|null  $state  State/province (nullable — not required in all countries).
     * @param  string|null  $postcode  Postal/ZIP code.
     * @param  string|null  $country  ISO country code, e.g. "US".
     * @param  string|null  $phone  Contact phone number.
     * @param  string|null  $email  Contact email address.
     * @param  string|null  $companyName  Optional company name.
     */
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $address,
        public readonly string $city,
        public readonly ?string $state = null,
        public readonly ?string $postcode = null,
        public readonly ?string $country = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $companyName = null,
    ) {}

    /**
     * Full recipient name derived from first and last name.
     */
    public function fullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }
}
