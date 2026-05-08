<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\Manager;

use Aurora\Core\Agency\DTO\AgencyInput;
use Aurora\Core\Agency\Entity\Agency;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Audit\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AgencyManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
    ) {}

    public function create(AgencyInput $input): AgencyInterface
    {
        $agency = new Agency()->setName($input->name);

        $this->entityManager->persist($agency);
        $this->entityManager->flush();

        $this->auditLogger->log('core', 'agency.created', 'Agency', $agency->getId(), ['name' => $agency->getName()]);

        return $agency;
    }

    public function update(AgencyInterface $agency, AgencyInput $input): void
    {
        $agency->setName($input->name);
        $this->entityManager->flush();

        $this->auditLogger->log('core', 'agency.updated', 'Agency', $agency->getId(), ['name' => $agency->getName()]);
    }

    public function delete(AgencyInterface $agency): void
    {
        $this->auditLogger->log('core', 'agency.deleted', 'Agency', $agency->getId(), ['name' => $agency->getName()]);

        $this->entityManager->remove($agency);
        $this->entityManager->flush();
    }
}
