<?php

declare(strict_types=1);

namespace App\Module\Crm\Contact\Serializer;

use App\Module\Crm\Contact\Entity\Contact;
use DateTimeInterface;

final readonly class ContactSerializer
{
    public function serialize(Contact $contact): array
    {
        return [
            'id' => $contact->getId(),
            'firstName' => $contact->getFirstName(),
            'lastName' => $contact->getLastName(),
            'fullName' => $contact->getFullName(),
            'email' => $contact->getEmail(),
            'phone' => $contact->getPhone(),
            'company' => $contact->getDisplayCompany(),
            'companyId' => $contact->getCompany()?->getId(),
            'notes' => $contact->getNotes(),
            'createdAt' => $contact->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $contact->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
