<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ProjectTaskCommentInput implements ProjectTaskCommentInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.projects.errors.comment_content_required')]
        #[Assert\Length(max: 4000, maxMessage: 'backend.projects.errors.comment_content_too_long')]
        public readonly string $content = '',
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }
}
