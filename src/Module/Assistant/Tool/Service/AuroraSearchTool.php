<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Tool\Service;

use Aurora\Module\Assistant\Tool\Contract\ToolInterface;
use Aurora\Core\Search\SearchProviderInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Throwable;

use function is_string;
use function sprintf;

/**
 * Aggregates every {@see SearchProviderInterface} contributed by Aurora
 * modules into one LLM-friendly answer. Each provider knows how to query
 * its own repositories and format the results — this tool just glues the
 * lines together. Adding a new searchable entity = drop a new provider;
 * no change here.
 *
 * Designed to stay free of any cross-module import (per the Aurora
 * "no cross-module dep" rule in src/Module/<X>/).
 */
final readonly class AuroraSearchTool implements ToolInterface
{
    /**
     * @param iterable<SearchProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator('aurora.search_provider')]
        private iterable $providers,
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
        return 'Search the Aurora backend (posts, taxonomy terms, media, projects, tasks, …) and return matching items as a compact list.';
    }

    public function getParameterSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Full-text query (1–80 chars). Matched against every search provider currently installed.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Max results per provider (default 5, max 10).',
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

        $lines = [];
        foreach ($this->providers as $provider) {
            try {
                foreach ($provider->search($query, $limit, $user) as $line) {
                    $lines[] = $line;
                }
            } catch (Throwable) {
                // A misbehaving provider must not poison the whole tool
                // — swallow and move on. The user still gets results from
                // every other module.
                continue;
            }
        }

        if ([] === $lines) {
            return sprintf('No results for "%s".', $query);
        }

        return implode("\n", $lines);
    }
}
