<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Crm\Company\Entity\Company;
use Aurora\Module\Project\Entity\Project;
use DateTimeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ProjectSerializer
{
    public function __construct(
        private TranslatorInterface $translator,
        private ProjectColumnSerializer $columnSerializer,
    ) {}

    public function serialize(Project $project): array
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
            'crmCompany' => $project->getCrmCompany() instanceof Company ? [
                'id' => $project->getCrmCompany()->getId(),
                'name' => $project->getCrmCompany()->getName(),
            ] : null,
            'columns' => array_map($this->columnSerializer->serialize(...), $project->getColumns()->toArray()),
            'taskCount' => $project->getTasks()->count(),
            'createdAt' => $project->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $project->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
