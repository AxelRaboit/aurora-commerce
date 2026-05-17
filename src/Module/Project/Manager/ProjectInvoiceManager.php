<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceInterface;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Entity\TiersInterface;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use Aurora\Module\Project\Entity\ProjectInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Generates a draft invoice from a project: pulls tiers from the linked CRM
 * company (creating one if missing) and pre-fills basic fields. The user
 * finalizes the invoice manually in the Billing module.
 */
#[AsAlias(ProjectInvoiceManagerInterface::class)]
class ProjectInvoiceManager implements ProjectInvoiceManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function generate(ProjectInterface $project): InvoiceInterface
    {
        $invoice = $this->createInvoice();
        $invoice->setStatus(InvoiceStatusEnum::Draft)
            ->setIssuedAt(new DateTimeImmutable())
            ->setDueAt(new DateTimeImmutable()->modify('+30 days'))
            ->setProject($project->getReference() ?? $project->getTitle());

        // Resolve or create a Tiers from the project's CRM company.
        $company = $project->getCrmCompany();
        if ($company instanceof CompanyInterface) {
            $tiers = $this->resolveOrCreateClientTiers($company->getName(), $company->getAddress());
            $invoice->setBuyerTiers($tiers);
        }

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'project.invoice.generated', 'Project', $project->getId(), [
            'projectId' => $project->getId(),
            'invoiceId' => $invoice->getId(),
        ]);

        return $invoice;
    }

    protected function createInvoice(): InvoiceInterface
    {
        return new Invoice();
    }

    protected function createTiers(): TiersInterface
    {
        return new Tiers();
    }

    private function resolveOrCreateClientTiers(string $name, ?string $address): TiersInterface
    {
        $repository = $this->entityManager->getRepository(Tiers::class);
        $existing = $repository->findOneBy(['name' => $name, 'type' => TiersTypeEnum::Client]);
        if ($existing instanceof TiersInterface) {
            return $existing;
        }

        $tiers = $this->createTiers();
        $tiers->setName($name)
            ->setType(TiersTypeEnum::Client)
            ->setAddress($address);
        $this->entityManager->persist($tiers);

        return $tiers;
    }
}
