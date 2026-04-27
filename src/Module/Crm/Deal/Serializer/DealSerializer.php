<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Serializer;

use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Deal\Entity\Deal;
use DateTimeInterface;

final readonly class DealSerializer
{
    public function serialize(Deal $deal): array
    {
        return [
            'id' => $deal->getId(),
            'name' => $deal->getName(),
            'stage' => $deal->getStage()->value,
            'value' => $deal->getValue(),
            'contact' => $deal->getContact() instanceof Contact ? [
                'id' => $deal->getContact()->getId(),
                'fullName' => $deal->getContact()->getFullName(),
            ] : null,
            'company' => $deal->getCompany() instanceof Company ? [
                'id' => $deal->getCompany()->getId(),
                'name' => $deal->getCompany()->getName(),
            ] : null,
            'closingDate' => $deal->getClosingDate()?->format('Y-m-d'),
            'notes' => $deal->getNotes(),
            'createdAt' => $deal->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $deal->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
