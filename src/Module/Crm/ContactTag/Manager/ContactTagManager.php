<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Module\Crm\ContactTag\Dto\ContactTagInputInterface;
use Aurora\Module\Crm\ContactTag\Entity\ContactTag;
use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;
use Aurora\Module\Crm\ContactTag\Repository\ContactTagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsAlias(ContactTagManagerInterface::class)]
class ContactTagManager implements ContactTagManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ContactTagRepository $contactTagRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly SluggerInterface $slugger,
    ) {}

    public function create(ContactTagInputInterface $input): ContactTagInterface
    {
        $contactTag = $this->createTag();
        $this->applyInput($contactTag, $input);

        $this->entityManager->persist($contactTag);
        $this->entityManager->flush();

        $this->auditCreated($contactTag);

        return $contactTag;
    }

    public function update(ContactTagInterface $contactTag, ContactTagInputInterface $input): void
    {
        $this->applyInput($contactTag, $input);
        $this->entityManager->flush();

        $this->auditUpdated($contactTag);
    }

    public function delete(ContactTagInterface $contactTag): void
    {
        $this->auditDeleted($contactTag);

        $this->entityManager->remove($contactTag);
        $this->entityManager->flush();
    }

    protected function createTag(): ContactTagInterface
    {
        return new ContactTag();
    }

    protected function applyInput(ContactTagInterface $contactTag, ContactTagInputInterface $input): void
    {
        $contactTag->setLabel($input->getLabel());
        $contactTag->setColor($input->getColor());

        $slug = $input->getSlug();
        if (null === $slug || '' === mb_trim($slug)) {
            $slug = $this->slugger->slug($input->getLabel())->lower()->toString();
        }

        $contactTag->setSlug($slug);
    }

    protected function auditCreated(ContactTagInterface $contactTag): void
    {
        $this->auditLogger->log('crm', 'contact_tag.created', 'ContactTag', $contactTag->getId(), $this->auditPayload($contactTag));
    }

    protected function auditUpdated(ContactTagInterface $contactTag): void
    {
        $this->auditLogger->log('crm', 'contact_tag.updated', 'ContactTag', $contactTag->getId(), $this->auditPayload($contactTag));
    }

    protected function auditDeleted(ContactTagInterface $contactTag): void
    {
        $this->auditLogger->log('crm', 'contact_tag.deleted', 'ContactTag', $contactTag->getId(), $this->auditPayload($contactTag));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(ContactTagInterface $contactTag): array
    {
        return [
            'label' => $contactTag->getLabel(),
            'slug' => $contactTag->getSlug(),
            'color' => $contactTag->getColor(),
        ];
    }
}
