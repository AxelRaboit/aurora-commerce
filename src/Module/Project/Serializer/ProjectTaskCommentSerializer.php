<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectTaskComment;
use DateTimeInterface;

final readonly class ProjectTaskCommentSerializer
{
    /** @return array<string, mixed> */
    public function serialize(ProjectTaskComment $comment): array
    {
        return [
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'authorId' => $comment->getAuthor()->getId(),
            'authorName' => $comment->getAuthor()->getName(),
            'createdAt' => $comment->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
