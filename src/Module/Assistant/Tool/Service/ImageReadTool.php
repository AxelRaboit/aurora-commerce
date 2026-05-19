<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Tool\Service;

use Aurora\Module\Assistant\MountPoint\Service\MountPointPathGuard;
use Aurora\Module\Assistant\Tool\Contract\ToolInterface;
use Aurora\Module\Assistant\Vision\Contract\VisionDescriberInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Throwable;

use function in_array;
use function is_string;
use function sprintf;

/**
 * Lets the chat model delegate image inspection to a vision model
 * (qwen2.5-vl by default). The chat model decides when to call it
 * — typically after a filesystem_read returned "binary, refusing to
 * return raw bytes" — and gets back a free-text description that it
 * can quote/summarise for the user.
 *
 * Same path-guard as {@see FilesystemReadTool}: the path must live
 * inside one of the user's active mount points. Read-only access
 * is enough; the file is never modified.
 */
final readonly class ImageReadTool implements ToolInterface
{
    /** Extensions the local Ollama vision models actually accept as inputs. */
    private const array SUPPORTED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

    public function __construct(
        private MountPointPathGuard $pathGuard,
        private VisionDescriberInterface $describer,
    ) {}

    public function getName(): string
    {
        return 'image_read';
    }

    public function requiresConfirmation(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return "Describe an image (PNG/JPG/WEBP/GIF) located under one of the user's mount points. Use this whenever filesystem_read refused a binary image or the user explicitly asks what is on a photo/screenshot.";
    }

    public function getParameterSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => "Absolute path of the image file to describe. Must lie inside one of the user's active mount points.",
                ],
                'question' => [
                    'type' => 'string',
                    'description' => 'Optional question or instruction to focus the description (e.g. "transcribe any text", "what brand is on the box?"). Defaults to a generic description prompt.',
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

        $question = isset($arguments['question']) && is_string($arguments['question']) ? mb_trim($arguments['question']) : '';

        $resolved = realpath($rawPath);
        if (false === $resolved) {
            return sprintf('Error: image does not exist: %s', $rawPath);
        }

        if (!is_file($resolved)) {
            return sprintf('Error: not a regular file: %s', $resolved);
        }

        $extension = mb_strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
        if (!in_array($extension, self::SUPPORTED_EXTENSIONS, true)) {
            return sprintf(
                'Error: unsupported image extension ".%s". Accepted: %s.',
                $extension,
                implode(', ', self::SUPPORTED_EXTENSIONS),
            );
        }

        if (!$this->pathGuard->isAllowed($resolved, $user)) {
            return sprintf('Error: image is outside any active mount point: %s', $resolved);
        }

        $prompt = '' !== $question
            ? sprintf("Look at the image and answer this question. Be specific and quote any text you can read.\n\nQuestion: %s", $question)
            : 'Describe the image in detail. If it contains readable text, transcribe it verbatim.';

        try {
            $description = $this->describer->describe($resolved, $prompt);
        } catch (Throwable $throwable) {
            return sprintf('Vision model failed: %s', $throwable->getMessage());
        }

        return sprintf("Image %s:\n%s", $resolved, $description);
    }
}
