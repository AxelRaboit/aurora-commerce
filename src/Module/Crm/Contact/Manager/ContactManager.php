<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Crm\Company\Repository\CompanyRepository;
use Aurora\Module\Crm\Contact\Dto\ContactInputInterface;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Contact\Entity\ContactInterface;
use Aurora\Module\Crm\Service\CrmNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ContactManagerInterface::class)]
class ContactManager implements ContactManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly CompanyRepository $companyRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly CrmNotificationService $notificationService,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
    ) {}

    public function create(ContactInputInterface $input): ContactInterface
    {
        $contact = $this->createContact();
        $this->applyInput($contact, $input);
        $prefix = $this->settingRepository->get(ApplicationParameterEnum::CrmContactPrefix->value, SequencePrefixEnum::Contact->value) ?? SequencePrefixEnum::Contact->value;
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
        return ['name' => $contact->getFullName(), 'reference' => $contact->getReference()];
    }
}
