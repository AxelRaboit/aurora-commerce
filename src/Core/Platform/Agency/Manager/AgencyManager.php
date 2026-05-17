<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Agency\Manager;

use Aurora\Core\Platform\Agency\Dto\AgencyInputInterface;
use Aurora\Core\Platform\Agency\Entity\Agency;
use Aurora\Core\Platform\Agency\Entity\AgencyInterface;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
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

        $this->auditCreated($agency);

        return $agency;
    }

    public function update(AgencyInterface $agency, AgencyInputInterface $input): void
    {
        $this->applyInput($agency, $input);
        $this->entityManager->flush();

        $this->auditUpdated($agency);
    }

    public function delete(AgencyInterface $agency): void
    {
        $this->auditDeleted($agency);

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

    protected function auditCreated(AgencyInterface $agency): void
    {
        $this->auditLogger->log('core', 'agency.created', 'Agency', $agency->getId(), $this->auditPayload($agency));
    }

    protected function auditUpdated(AgencyInterface $agency): void
    {
        $this->auditLogger->log('core', 'agency.updated', 'Agency', $agency->getId(), $this->auditPayload($agency));
    }

    protected function auditDeleted(AgencyInterface $agency): void
    {
        $this->auditLogger->log('core', 'agency.deleted', 'Agency', $agency->getId(), $this->auditPayload($agency));
    }

    /**
     * Returns the structured payload logged by every audit entry. Override in
     * a subclass to add extra fields: `[...parent::auditPayload($agency), 'code' => $agency->getCode()]`.
     */
    protected function auditPayload(AgencyInterface $agency): array
    {
        return ['name' => $agency->getName()];
    }
}
