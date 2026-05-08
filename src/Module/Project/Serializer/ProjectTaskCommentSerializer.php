<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectTaskCommentInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectTaskCommentSerializerInterface::class)]
class ProjectTaskCommentSerializer implements ProjectTaskCommentSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ProjectTaskCommentInterface $comment): array
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
