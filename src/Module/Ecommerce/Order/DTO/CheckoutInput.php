<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\DTO;

use Aurora\Core\Locale\Enum\CountryEnum;
use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CheckoutInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'front.checkout.errors.email_required')]
        #[Assert\Email(message: 'front.checkout.errors.email_invalid')]
        public string $email = '',
        #[Assert\NotBlank(message: 'front.checkout.errors.name_required')]
        #[Assert\Length(max: 200)]
        public string $name = '',
        #[Assert\Length(max: 200)]
        public ?string $addressLine1 = null,
        #[Assert\Length(max: 200)]
        public ?string $addressLine2 = null,
        #[Assert\Length(max: 100)]
        public ?string $city = null,
        #[Assert\Length(max: 20)]
        public ?string $postalCode = null,
        public ?string $country = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $country = Str::trimOrNullFromArray($data, 'country');

        return new self(
            email: Str::trimFromArray($data, 'email'),
            name: Str::trimFromArray($data, 'name'),
            addressLine1: Str::trimOrNullFromArray($data, 'addressLine1'),
            addressLine2: Str::trimOrNullFromArray($data, 'addressLine2'),
            city: Str::trimOrNullFromArray($data, 'city'),
            postalCode: Str::trimOrNullFromArray($data, 'postalCode'),
            country: null === $country ? null : mb_strtoupper($country),
            notes: Str::trimOrNullFromArray($data, 'notes'),
        );
    }

    /**
     * Returns a list of field => message for shipping fields that are required when the order
     * contains a physical product. Used by the checkout controller to add conditional errors
     * on top of the always-on Symfony constraints above.
     *
     * @return array<string, string>
     */
    public function shippingErrors(): array
    {
        $errors = [];
        if (null === $this->addressLine1 || '' === $this->addressLine1) {
            $errors['addressLine1'] = 'front.checkout.errors.address_required';
        }

        if (null === $this->city || '' === $this->city) {
            $errors['city'] = 'front.checkout.errors.city_required';
        }

        if (null === $this->postalCode || '' === $this->postalCode) {
            $errors['postalCode'] = 'front.checkout.errors.postal_required';
        }

        if (null === $this->country || '' === $this->country) {
            $errors['country'] = 'front.checkout.errors.country_required';
        } elseif (!in_array($this->country, CountryEnum::values(), true)) {
            $errors['country'] = 'front.checkout.errors.country_invalid';
        }

        return $errors;
    }
}
