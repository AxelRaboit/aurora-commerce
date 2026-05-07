<?php

declare(strict_types=1);

namespace Aurora\Module\Project\DTO;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProjectTaskCommentInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.projects.errors.comment_content_required')]
        #[Assert\Length(max: 4000, maxMessage: 'backend.projects.errors.comment_content_too_long')]
        public string $content = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(content: Str::trimFromArray($data, 'content'));
    }
}
