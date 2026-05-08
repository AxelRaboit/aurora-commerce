<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\Manager;

use Aurora\Core\Agency\Dto\AgencyInputInterface;
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
        $agency = $this->createAgency();
        $this->applyInput($agency, $input);

        $this->entityManager->persist($agency);
        $this->entityManager->flush();

        $this->auditLogger->log('core', 'agency.created', 'Agency', $agency->getId(), ['name' => $agency->getName()]);

        return $agency;
    }

    public function update(AgencyInterface $agency, AgencyInputInterface $input): void
    {
        $this->applyInput($agency, $input);
        $this->entityManager->flush();

        $this->auditLogger->log('core', 'agency.updated', 'Agency', $agency->getId(), ['name' => $agency->getName()]);
    }

    public function delete(AgencyInterface $agency): void
    {
        $this->auditLogger->log('core', 'agency.deleted', 'Agency', $agency->getId(), ['name' => $agency->getName()]);

        $this->entityManager->remove($agency);
        $this->entityManager->flush();
    }

    /**
     * Instantiates the concrete entity. Override in a subclass to instantiate
     * a client-specific class — `resolve_target_entities` only affects Doctrine
     * relation resolution, not direct `new`.
     */
    protected function createAgency(): AgencyInterface
    {
        return new Agency();
    }

    /**
     * Hydrates an agency from an input DTO. Override in a subclass and call
     * parent::applyInput() first to keep the base fields, then read your own
     * extra fields off the input.
     */
    protected function applyInput(AgencyInterface $agency, AgencyInputInterface $input): void
    {
        $agency->setName($input->getName());
    }
}
