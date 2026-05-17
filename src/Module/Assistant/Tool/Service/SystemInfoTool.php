<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Tool\Service;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\Tool\Contract\ToolInterface;
use Throwable;

use function in_array;
use function is_string;
use function sprintf;

/**
 * Runs a curated, hardcoded set of read-only diagnostic commands to help
 * the assistant answer "what version of X do I have?" questions without
 * asking the developer to run them manually.
 *
 * Security design:
 *  - No free-form shell input. The command is selected from a whitelist
 *    of safe, side-effect-free, non-destructive binaries only.
 *  - exec() is used with a fully-qualified binary invocation — no pipe,
 *    no redirection, no shell metacharacter can be injected because the
 *    $info argument is validated against a closed enum before use.
 *  - If exec() is disabled (shared hosting), every check returns a
 *    graceful "unavailable" message.
 *  - Timeout is enforced by the PHP process limit; each command should
 *    complete in < 1 s.
 */
final readonly class SystemInfoTool implements ToolInterface
{
    /** Closed list of allowed info keys → [binary, args] tuples. */
    private const array ALLOWED = [
        'php'      => ['php', '--version'],
        'node'     => ['node', '--version'],
        'composer' => ['composer', '--version', '--no-interaction'],
        'git'      => ['git', '--version'],
        'ollama'   => ['ollama', '--version'],
        'os'       => ['uname', '-srm'],
    ];

    public function getName(): string
    {
        return 'system_info';
    }

    public function requiresConfirmation(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return 'Return version / environment information for a specific tool (php, node, composer, git, ollama, os) or "all" for a full snapshot. Use when the user asks which version of a binary they have installed.';
    }

    public function getParameterSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'info' => [
                    'type' => 'string',
                    'description' => 'Which information to fetch: php, node, composer, git, ollama, os, or all.',
                    'enum' => [...array_keys(self::ALLOWED), 'all'],
                ],
            ],
            'required' => ['info'],
        ];
    }

    public function execute(array $arguments, CoreUserInterface $user): string
    {
        if (!$this->execAvailable()) {
            return 'Error: exec() is disabled on this PHP installation — cannot run diagnostic commands.';
        }

        $info = isset($arguments['info']) && is_string($arguments['info']) ? mb_strtolower(mb_trim($arguments['info'])) : '';

        if ('all' === $info) {
            $lines = [];
            foreach (self::ALLOWED as $key => $_) {
                $lines[] = $this->run($key);
            }

            return implode("\n", $lines);
        }

        if (!isset(self::ALLOWED[$info])) {
            return sprintf('Error: unknown info key "%s". Allowed: %s, all.', $info, implode(', ', array_keys(self::ALLOWED)));
        }

        return $this->run($info);
    }

    private function run(string $key): string
    {
        $cmd = self::ALLOWED[$key];
        $binary = array_shift($cmd);
        $safeArgs = implode(' ', array_map('escapeshellarg', $cmd));
        $fullCmd = escapeshellcmd($binary).' '.$safeArgs.' 2>&1';

        try {
            $output = [];
            $exitCode = 0;
            exec($fullCmd, $output, $exitCode);

            $result = implode(' ', array_map('trim', $output));
            if ('' === $result) {
                return sprintf('%s: (no output — binary may not be installed)', $key);
            }

            return sprintf('%s: %s', $key, $result);
        } catch (Throwable $throwable) {
            return sprintf('%s: error — %s', $key, $throwable->getMessage());
        }
    }

    private function execAvailable(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));

        return !in_array('exec', $disabled, true);
    }
}
