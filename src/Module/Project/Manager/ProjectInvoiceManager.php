<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Project\Entity\Project;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Generates a draft invoice from a project: pulls tiers from the linked CRM
 * company (creating one if missing) and pre-fills basic fields. The user
 * finalizes the invoice manually in the Billing module.
 */
final readonly class ProjectInvoiceManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
    ) {}

    public function generate(Project $project): Invoice
    {
        $invoice = new Invoice();
        $invoice->setStatus(InvoiceStatusEnum::Draft)
            ->setIssuedAt(new DateTimeImmutable())
            ->setDueAt(new DateTimeImmutable()->modify('+30 days'))
            ->setProject($project->getReference() ?? $project->getTitle());

        // Resolve or create a Tiers from the project's CRM company.
        $company = $project->getCrmCompany();
        if ($company instanceof Company) {
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

    private function resolveOrCreateClientTiers(string $name, ?string $address): Tiers
    {
        $repository = $this->entityManager->getRepository(Tiers::class);
        $existing = $repository->findOneBy(['name' => $name, 'type' => TiersTypeEnum::Client]);
        if ($existing instanceof Tiers) {
            return $existing;
        }

        $tiers = new Tiers();
        $tiers->setName($name)
            ->setType(TiersTypeEnum::Client)
            ->setAddress($address);
        $this->entityManager->persist($tiers);

        return $tiers;
    }
}
