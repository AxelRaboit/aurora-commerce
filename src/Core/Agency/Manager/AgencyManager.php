<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\Manager;

use Aurora\Core\Agency\DTO\AgencyInputInterface;
use Aurora\Core\Agency\Entity\Agency;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Aurora\Core\Audit\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AgencyManagerInterface::class)]
class AgencyManager implements AgencyManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(AgencyInputInterface $input): AgencyInterface
    {
        $agency = new Agency()->setName($input->getName());

        $this->entityManager->persist($agency);
        $this->entityManager->flush();

        $this->auditLogger->log('core', 'agency.created', 'Agency', $agency->getId(), ['name' => $agency->getName()]);

        return $agency;
    }

    public function update(AgencyInterface $agency, AgencyInputInterface $input): void
    {
        $agency->setName($input->getName());
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
