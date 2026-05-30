<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Reference;

use Aurora\Core\Reference\EntityReferenceProviderInterface;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;

/**
 * Resolves `crm.contact` soft references so other modules (Photo gallery,
 * Project, …) can display a linked contact without importing Crm. Its presence
 * also signals that the Crm module is installed.
 */
final readonly class ContactReferenceProvider implements EntityReferenceProviderInterface
{
    public function __construct(
        private ContactRepository $contactRepository,
    ) {}

    public function getType(): string
    {
        return 'crm.contact';
    }

    public function summarize(int $id): ?array
    {
        $contact = $this->contactRepository->find($id);

        return $contact instanceof ContactInterface ? [
            'id' => $contact->getId(),
            'name' => $contact->getFullName(),
            'email' => $contact->getEmail(),
        ] : null;
    }

    public function options(): array
    {
        return array_map(
            static fn ($contact): array => ['id' => (int) $contact->getId(), 'name' => $contact->getFullName()],
            $this->contactRepository->findAllOrderedByName(),
        );
    }
}
