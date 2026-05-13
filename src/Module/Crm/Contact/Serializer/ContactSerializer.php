<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Serializer;

use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ContactSerializerInterface::class)]
class ContactSerializer implements ContactSerializerInterface
{
    public function serialize(ContactInterface $contact): array
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
            'source' => $contact->getSource()?->value,
            'tags' => $contact->getTags(),
            'createdAt' => $contact->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $contact->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
