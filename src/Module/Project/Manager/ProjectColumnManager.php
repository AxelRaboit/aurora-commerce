<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Project\Dto\ProjectColumnInputInterface;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Entity\ProjectColumnInterface;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Repository\ProjectColumnRepository;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(ProjectColumnManagerInterface::class)]
class ProjectColumnManager implements ProjectColumnManagerInterface
{
    /**
     * Translation keys for the default columns seeded on project creation.
     * The labels are stored as text after translation — users can then rename them freely.
     */
    private const array DEFAULT_COLUMN_KEYS = [
        'backend.projects.columns.defaults.todo',
        'backend.projects.columns.defaults.in_progress',
        'backend.projects.columns.defaults.done',
    ];

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ProjectColumnRepository $columnRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly TranslatorInterface $translator,
    ) {}

    public function seedDefaults(ProjectInterface $project): array
    {
        $columns = [];
        foreach (self::DEFAULT_COLUMN_KEYS as $position => $key) {
            $column = $this->createProjectColumn();
            $column->setProject($project)
                ->setLabel($this->translator->trans($key))
                ->setPosition($position)
                ->setReference($this->sequenceGenerator->next(SequencePrefixEnum::ProjectColumn->value));
            $this->entityManager->persist($column);
            // Keep the in-memory collection in sync so callers can use
            // $project->getColumns() right after create() without re-fetching.
            $project->addColumn($column);
            $columns[] = $column;
        }

        return $columns;
    }

    public function create(ProjectInterface $project, ProjectColumnInputInterface $input): ProjectColumnInterface
    {
        $existing = $this->columnRepository->findByProject($project);
        $nextPosition = count($existing);

        $column = $this->createProjectColumn();
        $column->setProject($project)
            ->setLabel($input->getLabel())
            ->setPosition($nextPosition)
            ->setReference($this->sequenceGenerator->next(SequencePrefixEnum::ProjectColumn->value));
        $this->entityManager->persist($column);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'column.created', 'ProjectColumn', $column->getId(), [
            ...$this->auditPayload($column),
            'projectId' => $project->getId(),
        ]);

        return $column;
    }

    public function update(ProjectColumnInterface $column, ProjectColumnInputInterface $input): void
    {
        $previousLabel = $column->getLabel();
        $column->setLabel($input->getLabel());
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'column.updated', 'ProjectColumn', $column->getId(), [
            ...$this->auditPayload($column),
            'projectId' => $column->getProject()->getId(),
            'from' => $previousLabel,
            'to' => $column->getLabel(),
        ]);
    }

    public function delete(ProjectColumnInterface $column): void
    {
        $project = $column->getProject();
        $remaining = array_values(array_filter(
            $this->columnRepository->findByProject($project),
            static fn (ProjectColumnInterface $candidate): bool => $candidate->getId() !== $column->getId(),
        ));

        if ([] === $remaining) {
            // Cannot delete the last column — every task needs a home.
            throw new DomainException('backend.projects.errors.column_last_one');
        }

        // Move tasks to the first remaining column before removing.
        $fallback = $remaining[0];
        foreach ($column->getTasks() as $task) {
            $task->setColumn($fallback);
        }

        $columnId = $column->getId();
        $payload = $this->auditPayload($column);
        $this->entityManager->remove($column);
        $this->entityManager->flush();

        // Re-pack positions so they stay contiguous.
        foreach ($remaining as $position => $remainingColumn) {
            $remainingColumn->setPosition($position);
        }

        $this->entityManager->flush();

        $this->auditLogger->log('project', 'column.deleted', 'ProjectColumn', $columnId, [
            ...$payload,
            'projectId' => $project->getId(),
        ]);
    }

    /** @param list<int> $orderedIds */
    public function reorder(ProjectInterface $project, array $orderedIds): void
    {
        $columns = $this->columnRepository->findByProject($project);
        $indexed = [];
        foreach ($columns as $column) {
            $indexed[$column->getId()] = $column;
        }

        foreach ($orderedIds as $position => $columnId) {
            if (isset($indexed[$columnId])) {
                $indexed[$columnId]->setPosition($position);
            }
        }

        $this->entityManager->flush();

        $this->auditLogger->log('project', 'column.reordered', 'Project', $project->getId(), [
            'projectId' => $project->getId(),
            'columnIds' => $orderedIds,
        ]);
    }

    protected function createProjectColumn(): ProjectColumnInterface
    {
        return new ProjectColumn();
    }

    /**
     * Base payload for every ProjectColumn audit entry. Override to add custom
     * fields. Note: column lifecycle uses domain events (created, updated,
     * deleted, reordered) which splat-merge this payload inline.
     */
    protected function auditPayload(ProjectColumnInterface $column): array
    {
        return ['label' => $column->getLabel()];
    }
}
