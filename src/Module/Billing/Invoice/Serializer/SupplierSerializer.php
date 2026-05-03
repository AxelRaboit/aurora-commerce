<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Serializer;

use Aurora\Module\Billing\Invoice\Entity\Supplier;

final readonly class SupplierSerializer
{
    public function serialize(Supplier $supplier): array
    {
        return [
            'id' => $supplier->getId(),
            'name' => $supplier->getName(),
            'vatNumber' => $supplier->getVatNumber(),
            'registrationNumber' => $supplier->getRegistrationNumber(),
            'iban' => $supplier->getIban(),
            'bic' => $supplier->getBic(),
            'email' => $supplier->getEmail(),
            'phone' => $supplier->getPhone(),
            'address' => $supplier->getAddress(),
            'countryCode' => $supplier->getCountryCode(),
        ];
    }
}
