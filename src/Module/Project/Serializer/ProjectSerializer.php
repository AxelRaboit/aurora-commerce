<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Crm\Company\Entity\CompanyInterface;
use Aurora\Module\Crm\Deal\Entity\DealInterface;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectLabelInterface;
use Aurora\Module\Project\Repository\ProjectLabelRepository;
use Aurora\Module\Project\Repository\ProjectSprintRepository;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(ProjectSerializerInterface::class)]
class ProjectSerializer implements ProjectSerializerInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly ProjectColumnSerializerInterface $columnSerializer,
        protected readonly ProjectLabelRepository $labelRepository,
        protected readonly ProjectSprintRepository $sprintRepository,
        protected readonly ProjectSprintSerializerInterface $sprintSerializer,
    ) {}

    /** @return list<ProjectLabelInterface> */
    protected function labelsForProject(ProjectInterface $project): array
    {
        return $this->labelRepository->findByProject($project);
    }

    public function serialize(ProjectInterface $project): array
    {
        return [
            'id' => $project->getId(),
            'reference' => $project->getReference(),
            'title' => $project->getTitle(),
            'description' => $project->getDescription(),
            'status' => $project->getStatus()->value,
            'statusLabel' => $this->translator->trans($project->getStatus()->getLabelKey()),
            'startDate' => $project->getStartDate()?->format('Y-m-d'),
            'endDate' => $project->getEndDate()?->format('Y-m-d'),
            'responsibleUser' => $project->getResponsibleUser() instanceof User ? [
                'id' => $project->getResponsibleUser()->getId(),
                'name' => $project->getResponsibleUser()->getName(),
            ] : null,
            'crmContacts' => array_map(
                static fn ($contact): array => ['id' => $contact->getId(), 'name' => $contact->getFullName()],
                $project->getCrmContacts()->toArray(),
            ),
            'crmCompany' => $project->getCrmCompany() instanceof CompanyInterface ? [
                'id' => $project->getCrmCompany()->getId(),
                'name' => $project->getCrmCompany()->getName(),
            ] : null,
            'crmDeal' => $project->getCrmDeal() instanceof DealInterface ? [
                'id' => $project->getCrmDeal()->getId(),
                'name' => $project->getCrmDeal()->getName(),
            ] : null,
            'columns' => array_map($this->columnSerializer->serialize(...), $project->getColumns()->toArray()),
            'labels' => array_map(static fn ($label): array => [
                'id' => $label->getId(),
                'name' => $label->getName(),
                'color' => $label->getColor(),
            ], $this->labelsForProject($project)),
            'sprints' => array_map($this->sprintSerializer->serialize(...), $this->sprintRepository->findByProject($project)),
            'taskCount' => $project->getTasks()->count(),
            'createdAt' => $project->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $project->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
