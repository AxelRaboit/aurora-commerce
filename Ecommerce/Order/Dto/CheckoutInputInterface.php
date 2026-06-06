<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Dto;

interface CheckoutInputInterface
{
    public function getEmail(): string;

    public function getName(): string;

    public function getAddressLine1(): ?string;

    public function getAddressLine2(): ?string;

    public function getCity(): ?string;

    public function getPostalCode(): ?string;

    public function getCountry(): ?string;

    public function getNotes(): ?string;

    /**
     * Returns a list of field => message for shipping fields that are
     * required when the order contains a physical product.
     *
     * @return array<string, string>
     */
    public function shippingErrors(): array;
}
