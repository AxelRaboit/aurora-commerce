<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Crm\Company\Repository\CompanyRepository;
use Aurora\Module\Crm\Contact\Dto\ContactInputInterface;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\Contact\Enum\ContactSourceEnum;
use Aurora\Module\Crm\ContactTag\Repository\ContactTagRepository;
use Aurora\Module\Crm\Service\CrmNotificationService;
use Aurora\Module\Crm\Setting\CrmSettingEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ContactManagerInterface::class)]
class ContactManager implements ContactManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly CompanyRepository $companyRepository,
        protected readonly ContactTagRepository $contactTagRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly CrmNotificationService $notificationService,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
    ) {}

    public function create(ContactInputInterface $input): ContactInterface
    {
        $contact = $this->createContact();
        $this->applyInput($contact, $input);
        if (!$contact->getSource() instanceof ContactSourceEnum) {
            $contact->setSource(ContactSourceEnum::Manual);
        }

        $prefix = $this->settingRepository->getOrDefault(CrmSettingEnum::ContactPrefix);
        $contact->setReference($this->sequenceGenerator->next($prefix));
        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        $this->auditCreated($contact);

        $this->notificationService->notifyContactCreated($contact);

        return $contact;
    }

    public function update(ContactInterface $contact, ContactInputInterface $input): void
    {
        $this->applyInput($contact, $input);
        $this->entityManager->flush();

        $this->auditUpdated($contact);
    }

    public function delete(ContactInterface $contact): void
    {
        $this->auditDeleted($contact);

        $this->entityManager->remove($contact);
        $this->entityManager->flush();
    }

    protected function createContact(): ContactInterface
    {
        return new Contact();
    }

    protected function applyInput(ContactInterface $contact, ContactInputInterface $input): void
    {
        $contact->setFirstName($input->getFirstName());
        $contact->setLastName($input->getLastName());
        $contact->setEmail($input->getEmail());
        $contact->setPhone($input->getPhone());
        $contact->setCompany(null !== $input->getCompanyId() ? $this->companyRepository->find($input->getCompanyId()) : null);
        $contact->setNotes($input->getNotes());
        $this->applyTags($contact, $input);
    }

    protected function applyTags(ContactInterface $contact, ContactInputInterface $input): void
    {
        $contact->clearContactTags();
        $tagIds = $input->getTagIds();
        if ([] === $tagIds) {
            return;
        }

        $contactTags = $this->contactTagRepository->findBy(['id' => $tagIds]);
        foreach ($contactTags as $contactTag) {
            $contact->addContactTag($contactTag);
        }
    }

    protected function auditCreated(ContactInterface $contact): void
    {
        $this->auditLogger->log('crm', 'contact.created', 'Contact', $contact->getId(), $this->auditPayload($contact));
    }

    protected function auditUpdated(ContactInterface $contact): void
    {
        $this->auditLogger->log('crm', 'contact.updated', 'Contact', $contact->getId(), $this->auditPayload($contact));
    }

    protected function auditDeleted(ContactInterface $contact): void
    {
        $this->auditLogger->log('crm', 'contact.deleted', 'Contact', $contact->getId(), $this->auditPayload($contact));
    }

    protected function auditPayload(ContactInterface $contact): array
    {
        $contactTagIds = [];
        foreach ($contact->getContactTags() as $contactTag) {
            $contactTagIds[] = $contactTag->getId();
        }

        return [
            'name' => $contact->getFullName(),
            'reference' => $contact->getReference(),
            'source' => $contact->getSource()?->value,
            'contact_tag_ids' => $contactTagIds,
        ];
    }
}
