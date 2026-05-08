<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Crm\Company\Repository\CompanyRepository;
use Aurora\Module\Crm\Contact\Contract\ContactManagerInterface;
use Aurora\Module\Crm\Contact\Dto\ContactInput;
use Aurora\Module\Crm\Contact\Entity\Contact;
use Aurora\Module\Crm\Service\CrmNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ContactManagerInterface::class)]
final readonly class ContactManager implements ContactManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyRepository $companyRepository,
        private AuditLogger $auditLogger,
        private CrmNotificationService $notificationService,
        private SequenceGenerator $sequenceGenerator,
        private SettingRepository $settingRepository,
    ) {}

    public function create(ContactInput $input): Contact
    {
        $contact = new Contact();
        $this->applyInput($contact, $input);
        $prefix = $this->settingRepository->get(ApplicationParameterEnum::CrmContactPrefix->value, SequencePrefixEnum::Contact->value) ?? SequencePrefixEnum::Contact->value;
        $contact->setReference($this->sequenceGenerator->next($prefix));
        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'contact.created', 'Contact', $contact->getId(), ['name' => $contact->getFullName(), 'reference' => $contact->getReference()]);

        $this->notificationService->notifyContactCreated($contact);

        return $contact;
    }

    public function update(Contact $contact, ContactInput $input): void
    {
        $this->applyInput($contact, $input);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'contact.updated', 'Contact', $contact->getId(), ['name' => $contact->getFullName()]);
    }

    public function delete(Contact $contact): void
    {
        $name = $contact->getFullName();
        $id = $contact->getId();

        $this->entityManager->remove($contact);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'contact.deleted', 'Contact', $id, ['name' => $name]);
    }

    private function applyInput(Contact $contact, ContactInput $input): void
    {
        $contact->setFirstName($input->firstName);
        $contact->setLastName($input->lastName);
        $contact->setEmail($input->email);
        $contact->setPhone($input->phone);
        $contact->setCompany($input->companyId ? $this->companyRepository->find($input->companyId) : null);
        $contact->setNotes($input->notes);
    }
}
