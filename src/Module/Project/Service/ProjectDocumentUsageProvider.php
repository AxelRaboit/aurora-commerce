<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Service;

use Aurora\Module\Ged\Document\Contract\DocumentUsageProviderInterface;
use Aurora\Module\Project\Repository\ProjectTaskRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * Reports project tasks that attach the given GED document
 * (`ProjectTask.attachments`, ManyToMany via `core_project_task_documents`).
 */
final readonly class ProjectDocumentUsageProvider implements DocumentUsageProviderInterface
{
    public function __construct(
        private ProjectTaskRepository $taskRepository,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {}

    public function findUsages(int $documentId): array
    {
        $tasks = $this->taskRepository->createQueryBuilder('t')
            ->innerJoin('t.attachments', 'a')
            ->andWhere('a.id = :id')
            ->setParameter('id', $documentId)
            ->getQuery()
            ->getResult();

        $usages = [];
        foreach ($tasks as $task) {
            $usages[] = [
                'type' => 'project.task',
                'label' => $task->getTitle(),
                'detail' => $this->translator->trans('backend.ged.documents.usage.project_task'),
                'href' => $this->safeUrl('backend_project_projects_show', ['id' => (int) $task->getProject()->getId()]),
            ];
        }

        return $usages;
    }

    /** @param array<string, mixed> $params */
    private function safeUrl(string $route, array $params): ?string
    {
        try {
            return $this->urlGenerator->generate($route, $params);
        } catch (Throwable) {
            return null;
        }
    }
}
