<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Serializer;

use Aurora\Module\Billing\Invoice\Entity\TiersInterface;

final readonly class TiersSerializer
{
    public function serialize(TiersInterface $tiers): array
    {
        return [
            'id' => $tiers->getId(),
            'type' => $tiers->getType()->value,
            'name' => $tiers->getName(),
            'vatNumber' => $tiers->getVatNumber(),
            'registrationNumber' => $tiers->getRegistrationNumber(),
            'iban' => $tiers->getIban(),
            'bic' => $tiers->getBic(),
            'email' => $tiers->getEmail(),
            'phone' => $tiers->getPhone(),
            'address' => $tiers->getAddress(),
            'countryCode' => $tiers->getCountryCode(),
            'website' => $tiers->getWebsite(),
            'legalForm' => $tiers->getLegalForm(),
            'bankName' => $tiers->getBankName(),
            'notes' => $tiers->getNotes(),
        ];
    }
}
