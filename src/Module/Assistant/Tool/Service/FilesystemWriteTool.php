<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Tool\Service;

use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\MountPoint\Service\MountPointPathGuard;
use Aurora\Module\Assistant\Tool\Contract\ToolInterface;

use function dirname;
use function is_string;
use function sprintf;

/**
 * Writes a UTF-8 text file under one of the user's *ReadWrite* mount
 * points. Always behind the confirmation gate ({@see requiresConfirmation})
 * — the manager halts the chat loop and waits for explicit user approval
 * before execute() ever runs.
 *
 * Path resolution mirrors {@see FilesystemReadTool}: realpath() on the
 * parent directory + filename, prefix-match against active ReadWrite
 * mount points. Files outside the allowlist, attempts to overwrite
 * directories, and binary payloads are refused.
 */
final readonly class FilesystemWriteTool implements ToolInterface
{
    /** Cap on accepted payload size (UTF-8). */
    private const int MAX_CONTENT_BYTES = 256 * 1024;

    public function __construct(
        private MountPointPathGuard $pathGuard,
    ) {}

    public function getName(): string
    {
        return 'filesystem_write';
    }

    public function requiresConfirmation(): bool
    {
        return true;
    }

    public function getDescription(): string
    {
        return 'Write a UTF-8 text file under one of the user\'s ReadWrite mount points. The user must approve the call explicitly — propose meaningful diffs only.';
    }

    public function getParameterSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Absolute filesystem path to write. Must lie inside a ReadWrite mount point. Parent directory must exist.',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Full file content (UTF-8, max 256KB). The file is overwritten atomically.',
                ],
            ],
            'required' => ['path', 'content'],
        ];
    }

    public function execute(array $arguments, CoreUserInterface $user): string
    {
        $rawPath = isset($arguments['path']) && is_string($arguments['path']) ? mb_trim($arguments['path']) : '';
        $content = isset($arguments['content']) && is_string($arguments['content']) ? $arguments['content'] : null;
        if ('' === $rawPath || null === $content) {
            return 'Error: "path" and "content" are required.';
        }

        if (mb_strlen($content, '8bit') > self::MAX_CONTENT_BYTES) {
            return sprintf('Error: content exceeds %d KB cap.', self::MAX_CONTENT_BYTES / 1024);
        }

        if (str_contains($content, "\0")) {
            return 'Error: content contains NUL bytes — refusing binary payload.';
        }

        $parentDir = realpath(dirname($rawPath));
        if (false === $parentDir) {
            return sprintf('Error: parent directory does not exist: %s', dirname($rawPath));
        }

        $resolved = $parentDir.'/'.basename($rawPath);

        if (!$this->pathGuard->isAllowed($resolved, $user, requireWrite: true)) {
            return sprintf('Error: path is outside any active ReadWrite mount point: %s', $resolved);
        }

        if (is_dir($resolved)) {
            return sprintf('Error: refusing to overwrite directory: %s', $resolved);
        }

        $existed = is_file($resolved);
        $tmp = $resolved.'.tmp.'.bin2hex(random_bytes(4));
        if (false === @file_put_contents($tmp, $content)) {
            return sprintf('Error: write failed: %s', $resolved);
        }

        if (!@rename($tmp, $resolved)) {
            @unlink($tmp);

            return sprintf('Error: atomic rename failed: %s', $resolved);
        }

        return sprintf(
            '%s %s (%d bytes).',
            $existed ? 'Updated' : 'Created',
            $resolved,
            mb_strlen($content, '8bit'),
        );
    }
}
