<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Module\Project\DTO\ProjectColumnInput;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Repository\ProjectColumnRepository;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ProjectColumnManager
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
        private EntityManagerInterface $entityManager,
        private ProjectColumnRepository $columnRepository,
        private AuditLogger $auditLogger,
        private SequenceGenerator $sequenceGenerator,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Seed the 3 default Kanban columns when a project is created.
     *
     * @return list<ProjectColumn>
     */
    public function seedDefaults(Project $project): array
    {
        $columns = [];
        foreach (self::DEFAULT_COLUMN_KEYS as $position => $key) {
            $column = new ProjectColumn();
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

    public function create(Project $project, ProjectColumnInput $input): ProjectColumn
    {
        $existing = $this->columnRepository->findByProject($project);
        $nextPosition = count($existing);

        $column = new ProjectColumn();
        $column->setProject($project)
            ->setLabel($input->label)
            ->setPosition($nextPosition)
            ->setReference($this->sequenceGenerator->next(SequencePrefixEnum::ProjectColumn->value));
        $this->entityManager->persist($column);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'column.created', 'ProjectColumn', $column->getId(), [
            'projectId' => $project->getId(),
            'label' => $column->getLabel(),
        ]);

        return $column;
    }

    public function update(ProjectColumn $column, ProjectColumnInput $input): void
    {
        $previousLabel = $column->getLabel();
        $column->setLabel($input->label);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'column.updated', 'ProjectColumn', $column->getId(), [
            'projectId' => $column->getProject()->getId(),
            'from' => $previousLabel,
            'to' => $column->getLabel(),
        ]);
    }

    public function delete(ProjectColumn $column): void
    {
        $project = $column->getProject();
        $remaining = array_values(array_filter(
            $this->columnRepository->findByProject($project),
            static fn (ProjectColumn $candidate): bool => $candidate->getId() !== $column->getId(),
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
        $columnLabel = $column->getLabel();
        $this->entityManager->remove($column);
        $this->entityManager->flush();

        // Re-pack positions so they stay contiguous.
        foreach ($remaining as $position => $remainingColumn) {
            $remainingColumn->setPosition($position);
        }

        $this->entityManager->flush();

        $this->auditLogger->log('project', 'column.deleted', 'ProjectColumn', $columnId, [
            'projectId' => $project->getId(),
            'label' => $columnLabel,
        ]);
    }

    /** @param list<int> $orderedIds */
    public function reorder(Project $project, array $orderedIds): void
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
}
