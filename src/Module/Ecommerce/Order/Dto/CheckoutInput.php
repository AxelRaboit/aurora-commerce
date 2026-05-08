<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Dto;

use Aurora\Core\Locale\Enum\CountryEnum;
use Symfony\Component\Validator\Constraints as Assert;

class CheckoutInput implements CheckoutInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'frontend.checkout.errors.email_required')]
        #[Assert\Email(message: 'frontend.checkout.errors.email_invalid')]
        public readonly string $email = '',
        #[Assert\NotBlank(message: 'frontend.checkout.errors.name_required')]
        #[Assert\Length(max: 200)]
        public readonly string $name = '',
        #[Assert\Length(max: 200)]
        public readonly ?string $addressLine1 = null,
        #[Assert\Length(max: 200)]
        public readonly ?string $addressLine2 = null,
        #[Assert\Length(max: 100)]
        public readonly ?string $city = null,
        #[Assert\Length(max: 20)]
        public readonly ?string $postalCode = null,
        public readonly ?string $country = null,
        public readonly ?string $notes = null,
    ) {}

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function shippingErrors(): array
    {
        $errors = [];
        if (null === $this->addressLine1 || '' === $this->addressLine1) {
            $errors['addressLine1'] = 'frontend.checkout.errors.address_required';
        }

        if (null === $this->city || '' === $this->city) {
            $errors['city'] = 'frontend.checkout.errors.city_required';
        }

        if (null === $this->postalCode || '' === $this->postalCode) {
            $errors['postalCode'] = 'frontend.checkout.errors.postal_required';
        }

        if (null === $this->country || '' === $this->country) {
            $errors['country'] = 'frontend.checkout.errors.country_required';
        } elseif (!in_array($this->country, CountryEnum::values(), true)) {
            $errors['country'] = 'frontend.checkout.errors.country_invalid';
        }

        return $errors;
    }
}
