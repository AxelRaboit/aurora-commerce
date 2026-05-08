<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CheckoutInputFactoryInterface::class)]
class CheckoutInputFactory implements CheckoutInputFactoryInterface
{
    public function fromArray(array $data): CheckoutInputInterface
    {
        $country = Str::trimOrNullFromArray($data, 'country');

        return new CheckoutInput(
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
}
