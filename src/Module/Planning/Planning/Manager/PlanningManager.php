<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Planning\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Planning\Planning\Dto\PlanningInputInterface;
use Aurora\Module\Planning\Planning\Entity\Planning;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use Aurora\Module\Platform\Agency\Repository\AgencyRepository;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PlanningManagerInterface::class)]
class PlanningManager implements PlanningManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly UserRepository $userRepository,
        protected readonly AgencyRepository $agencyRepository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(PlanningInputInterface $input): PlanningInterface
    {
        $planning = $this->createPlanning();
        $this->applyInput($planning, $input);
        $this->entityManager->persist($planning);
        $this->entityManager->flush();

        $this->auditCreated($planning);

        return $planning;
    }

    public function update(PlanningInterface $planning, PlanningInputInterface $input): void
    {
        $this->applyInput($planning, $input);
        $this->entityManager->flush();

        $this->auditUpdated($planning);
    }

    public function delete(PlanningInterface $planning): void
    {
        $this->auditDeleted($planning);

        $this->entityManager->remove($planning);
        $this->entityManager->flush();
    }

    protected function createPlanning(): PlanningInterface
    {
        return new Planning();
    }

    protected function applyInput(PlanningInterface $planning, PlanningInputInterface $input): void
    {
        $planning->setName($input->getName());
        $planning->setDescription($input->getDescription());
        $planning->setColor($input->getColor());
        $planning->setTimezone($input->getTimezone());
        $planning->setVisibility($input->getVisibilityEnum());
        $planning->setOwner(null !== $input->getOwnerId() ? $this->userRepository->find($input->getOwnerId()) : null);
        $planning->setAgency(null !== $input->getAgencyId() ? $this->agencyRepository->find($input->getAgencyId()) : null);
    }

    protected function auditCreated(PlanningInterface $planning): void
    {
        $this->auditLogger->log('planning', 'planning.created', 'Planning', $planning->getId(), $this->auditPayload($planning));
    }

    protected function auditUpdated(PlanningInterface $planning): void
    {
        $this->auditLogger->log('planning', 'planning.updated', 'Planning', $planning->getId(), $this->auditPayload($planning));
    }

    protected function auditDeleted(PlanningInterface $planning): void
    {
        $this->auditLogger->log('planning', 'planning.deleted', 'Planning', $planning->getId(), $this->auditPayload($planning));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PlanningInterface $planning): array
    {
        return [
            'name' => $planning->getName(),
            'visibility' => $planning->getVisibility()->value,
            'ownerId' => $planning->getOwner()?->getId(),
            'agencyId' => $planning->getAgency()?->getId(),
        ];
    }
}
