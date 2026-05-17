<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Tool\Service;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\Tool\Contract\ToolInterface;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use Aurora\Module\Project\Repository\ProjectRepository;
use Aurora\Module\Project\Repository\ProjectTaskRepository;

use function is_string;
use function sprintf;

/**
 * Wraps the same repositories the global backend search (SearchController)
 * uses, but returns a compact text payload tuned for an LLM consumer:
 *
 *   [POST #42] (status=published) My title
 *   [PROJECT #7] (status=active, ref=PRJ-007) My project
 *   …
 *
 * The LLM uses this when the user asks for "find X", "where did I store Y",
 * "list my recent projects matching …", etc.
 */
final readonly class AuroraSearchTool implements ToolInterface
{
    public function __construct(
        private PostRepository $postRepository,
        private TaxonomyTermRepository $termRepository,
        private MediaRepository $mediaRepository,
        private ProjectRepository $projectRepository,
        private ProjectTaskRepository $taskRepository,
        private LocaleContextInterface $localeContext,
    ) {}

    public function getName(): string
    {
        return 'aurora_search';
    }

    public function requiresConfirmation(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return 'Search the Aurora backend (posts, taxonomy terms, media, projects, tasks) and return matching items as a compact list.';
    }

    public function getParameterSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Full-text query (1–80 chars). Matches post content, term name, media name, project/task title.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Max results per category (default 5, max 10).',
                    'minimum' => 1,
                    'maximum' => 10,
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function execute(array $arguments, CoreUserInterface $user): string
    {
        $query = isset($arguments['query']) && is_string($arguments['query']) ? mb_trim($arguments['query']) : '';
        if ('' === $query) {
            return 'Error: empty query.';
        }

        $limit = isset($arguments['limit']) ? max(1, min(10, (int) $arguments['limit'])) : 5;
        $defaultLocale = $this->localeContext->getDefaultLocale();

        $lines = [];

        $postIds = $this->postRepository->fullTextPostIds($query, $limit);
        if ([] !== $postIds) {
            foreach ($this->postRepository->findByIds($postIds) as $post) {
                $title = $post->getTranslation($defaultLocale)?->getTitle()
                    ?? ($post->getTranslations()->first() ?: null)?->getTitle()
                    ?? '(untitled)';
                $lines[] = sprintf('[POST #%d] (status=%s) %s', $post->getId(), $post->getStatus()->value, $title);
            }
        }

        foreach ($this->termRepository->searchByName($query, $limit) as $term) {
            $name = $term->getTranslation($defaultLocale)?->getName()
                ?? ($term->getTranslations()->first() ?: null)?->getName()
                ?? '(unnamed)';
            $lines[] = sprintf('[TERM #%d] (taxonomy=%s) %s', $term->getId(), $term->getTaxonomy()->getSlug(), $name);
        }

        foreach ($this->mediaRepository->searchByName($query, $limit) as $media) {
            $lines[] = sprintf('[MEDIA #%d] %s (%s)', $media->getId(), (string) $media->getOriginalName(), (string) $media->getMimeType());
        }

        foreach ($this->projectRepository->searchByTitle($query) as $project) {
            $lines[] = sprintf('[PROJECT #%d] (status=%s, ref=%s) %s', $project->getId(), $project->getStatus()->value, (string) $project->getReference(), (string) $project->getTitle());
        }

        foreach ($this->taskRepository->searchByTitle($query) as $task) {
            $lines[] = sprintf('[TASK #%d] (ref=%s, project=%s) %s', $task->getId(), (string) $task->getReference(), (string) $task->getProject()->getTitle(), (string) $task->getTitle());
        }

        if ([] === $lines) {
            return sprintf('No results for "%s".', $query);
        }

        return implode("\n", $lines);
    }
}
