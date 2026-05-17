<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Tool\Registry;

use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\Tool\Contract\ToolInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

use function sprintf;

/**
 * Collects every service tagged `aurora.assistant.tool` and exposes them
 * to the Manager: the OpenAI/Ollama-compatible tool descriptors for the
 * LLM, and a name→tool map for dispatching back the tool calls the LLM
 * emits.
 *
 * Adding a new tool = implement {@see ToolInterface}; the autoconfig
 * tag picks it up — no registry edit required.
 */
final readonly class ToolRegistry
{
    /** @var array<string, ToolInterface> */
    private array $byName;

    /**
     * @param iterable<ToolInterface> $tools
     */
    public function __construct(
        #[AutowireIterator('aurora.assistant.tool')]
        iterable $tools,
    ) {
        $map = [];
        foreach ($tools as $tool) {
            $map[$tool->getName()] = $tool;
        }

        $this->byName = $map;
    }

    /**
     * Tool descriptors in the OpenAI/Ollama function-calling shape.
     *
     * @return list<array<string, mixed>>
     */
    public function describe(): array
    {
        $out = [];
        foreach ($this->byName as $tool) {
            $out[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'parameters' => $tool->getParameterSchema(),
                ],
            ];
        }

        return $out;
    }

    public function has(string $name): bool
    {
        return isset($this->byName[$name]);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function execute(string $name, array $arguments, CoreUserInterface $user): string
    {
        if (!isset($this->byName[$name])) {
            throw new RuntimeException(sprintf('Unknown tool: %s', $name));
        }

        return $this->byName[$name]->execute($arguments, $user);
    }
}
