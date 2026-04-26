<?php

declare(strict_types=1);

namespace App\Module\Crm\Contact\Manager;

use App\Core\Audit\Service\AuditLogger;
use App\Module\Crm\Company\Repository\CompanyRepository;
use App\Module\Crm\Contact\Contract\ContactManagerInterface;
use App\Module\Crm\Contact\DTO\ContactInput;
use App\Module\Crm\Contact\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ContactManagerInterface::class)]
final readonly class ContactManager implements ContactManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyRepository $companyRepository,
        private AuditLogger $auditLogger,
    ) {}

    public function create(ContactInput $input): Contact
    {
        $contact = new Contact();
        $this->applyInput($contact, $input);
        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        $this->auditLogger->log('crm', 'contact.created', 'Contact', $contact->getId(), ['name' => $contact->getFullName()]);

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
