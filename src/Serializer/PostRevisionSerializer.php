<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\PostRevision;
use DateTimeInterface;

final readonly class PostRevisionSerializer
{
    public function serialize(PostRevision $revision): array
    {
        $author = $revision->getAuthor();

        return [
            'id' => $revision->getId(),
            'postVersion' => $revision->getPostVersion(),
            'status' => $revision->getStatus()->value,
            'createdAt' => $revision->getCreatedAt()->format(DateTimeInterface::ATOM),
            'author' => null !== $author ? [
                'id' => $author->getId(),
                'email' => $author->getEmail(),
            ] : null,
        ];
    }

    public function serializeFull(PostRevision $revision): array
    {
        return [
            ...$this->serialize($revision),
            'snapshot' => $revision->getSnapshot(),
        ];
    }
}
