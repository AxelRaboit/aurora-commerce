<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Tool\Service;

use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\MountPoint\Service\MountPointPathGuard;
use Aurora\Module\Assistant\Tool\Contract\ToolInterface;

use function count;
use function dirname;
use function in_array;
use function is_string;
use function sprintf;

/**
 * Reads files and directory listings from the user's configured mount
 * points. Operates with a strict three-layer guard:
 *
 *   1. The requested path is resolved with realpath() to collapse `..`,
 *      symlinks, and trailing slashes.
 *   2. The resolved path must lie inside one of the *active* mount points
 *      attached to the conversation's owning user. Inactive entries are
 *      skipped; read/write distinction is irrelevant for reading.
 *   3. Dotfiles (starting with `.`) and non-regular files are excluded
 *      from listings and refused for reads.
 *
 * Returns text payloads aimed at an LLM consumer — short, deterministic,
 * and ready to splice into the next chat turn.
 */
final readonly class FilesystemReadTool implements ToolInterface
{
    /** Hard cap on the bytes returned per file — keep the LLM context window safe. */
    private const int MAX_FILE_BYTES = 64 * 1024;

    /** Hard cap on entries returned per directory listing. */
    private const int MAX_DIR_ENTRIES = 200;

    public function __construct(
        private MountPointPathGuard $pathGuard,
    ) {}

    public function getName(): string
    {
        return 'filesystem_read';
    }

    public function requiresConfirmation(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return "Read a file or list a directory under one of the user's configured mount points. Returns text content (text files only, capped at 64KB) or a sorted list of entries (capped at 200).";
    }

    public function getParameterSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => "Absolute filesystem path to read. Must lie inside one of the user's active mount points.",
                ],
                'mode' => [
                    'type' => 'string',
                    'description' => 'Read mode: "auto" (infer from path), "list" (force directory listing), "file" (force file read).',
                    'enum' => ['auto', 'list', 'file'],
                ],
            ],
            'required' => ['path'],
        ];
    }

    public function execute(array $arguments, CoreUserInterface $user): string
    {
        $rawPath = isset($arguments['path']) && is_string($arguments['path']) ? mb_trim($arguments['path']) : '';
        if ('' === $rawPath) {
            return 'Error: missing "path" argument.';
        }

        $mode = isset($arguments['mode']) && is_string($arguments['mode']) ? $arguments['mode'] : 'auto';
        if (!in_array($mode, ['auto', 'list', 'file'], true)) {
            $mode = 'auto';
        }

        $resolved = realpath($rawPath);
        if (false === $resolved) {
            return $this->describeMissingPath($rawPath, $user);
        }

        if (!$this->pathGuard->isAllowed($resolved, $user)) {
            return sprintf('Error: path is outside any active mount point: %s', $resolved);
        }

        if ('list' === $mode || ('auto' === $mode && is_dir($resolved))) {
            return $this->listDirectory($resolved);
        }

        return $this->readFile($resolved);
    }

    /**
     * Build a "path not found" message that includes up to 5 sibling
     * entries whose names start with the missing filename (case-
     * insensitive). Small LLMs often guess `README` when the file is
     * `README.md` — surfacing the candidates lets the next turn fix
     * itself without a separate `list` roundtrip.
     */
    private function describeMissingPath(string $rawPath, CoreUserInterface $user): string
    {
        $parent = dirname($rawPath);
        $needle = mb_strtolower(basename($rawPath));
        $resolvedParent = realpath($parent);

        if (false === $resolvedParent || !$this->pathGuard->isAllowed($resolvedParent, $user) || !is_dir($resolvedParent)) {
            return sprintf('Error: path does not exist: %s', $rawPath);
        }

        $entries = @scandir($resolvedParent);
        if (false === $entries) {
            return sprintf('Error: path does not exist: %s', $rawPath);
        }

        $candidates = [];
        foreach ($entries as $entry) {
            if (in_array($entry, ['.', '..'], true)) {
                continue;
            }

            if (str_starts_with($entry, '.')) {
                continue;
            }

            $lower = mb_strtolower($entry);
            if (str_starts_with($lower, $needle) || str_contains($lower, $needle)) {
                $candidates[] = $entry;
            }

            if (count($candidates) >= 5) {
                break;
            }
        }

        if ([] === $candidates) {
            return sprintf('Error: path does not exist: %s', $rawPath);
        }

        return sprintf(
            "Error: path does not exist: %s\nDid you mean one of: %s ? Retry with the full filename.",
            $rawPath,
            implode(', ', $candidates),
        );
    }

    private function listDirectory(string $path): string
    {
        if (!is_dir($path)) {
            return sprintf('Error: not a directory: %s', $path);
        }

        $entries = @scandir($path);
        if (false === $entries) {
            return sprintf('Error: cannot read directory: %s', $path);
        }

        $lines = [];
        $count = 0;
        foreach ($entries as $entry) {
            if (in_array($entry, ['.', '..'], true)) {
                continue;
            }

            if (str_starts_with($entry, '.')) {
                continue;
            }

            $full = $path.'/'.$entry;
            if (is_dir($full)) {
                $lines[] = $entry.'/';
            } elseif (is_file($full)) {
                $lines[] = sprintf('%s (%s)', $entry, $this->humanSize((int) filesize($full)));
            }

            ++$count;
            if ($count >= self::MAX_DIR_ENTRIES) {
                $lines[] = sprintf('… (truncated at %d entries)', self::MAX_DIR_ENTRIES);
                break;
            }
        }

        sort($lines);

        if ([] === $lines) {
            return sprintf('Directory %s is empty.', $path);
        }

        return sprintf("Directory %s:\n%s", $path, implode("\n", $lines));
    }

    private function readFile(string $path): string
    {
        if (!is_file($path)) {
            return sprintf('Error: not a regular file: %s', $path);
        }

        if (!is_readable($path)) {
            return sprintf('Error: file not readable: %s', $path);
        }

        $size = (int) filesize($path);
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            return sprintf('Error: cannot open: %s', $path);
        }

        try {
            $chunk = (string) fread($handle, self::MAX_FILE_BYTES);
        } finally {
            fclose($handle);
        }

        if (!$this->looksTextual($chunk)) {
            return sprintf('Error: file appears binary (%s, %s) — refusing to return raw bytes.', $path, $this->humanSize($size));
        }

        $truncated = $size > self::MAX_FILE_BYTES;
        $header = sprintf('File %s (%s%s):', $path, $this->humanSize($size), $truncated ? ', truncated to 64KB' : '');

        return $header."\n".$chunk;
    }

    /**
     * Heuristic: a chunk is "textual" if it contains no NUL byte. Good
     * enough for the small files we accept; a more nuanced detector
     * (UTF-8 validity, BOM, etc.) isn't worth the complexity here.
     */
    private function looksTextual(string $chunk): bool
    {
        if ('' === $chunk) {
            return true;
        }

        return !str_contains($chunk, "\0");
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return sprintf('%.1f KB', $bytes / 1024);
        }

        return sprintf('%.1f MB', $bytes / (1024 * 1024));
    }
}
