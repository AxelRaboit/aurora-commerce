<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\Serializer;

use Aurora\Module\Notes\PostIt\Entity\PostItNoteInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PostItNoteSerializerInterface::class)]
class PostItNoteSerializer implements PostItNoteSerializerInterface
{
    public function serialize(PostItNoteInterface $note): array
    {
        return [
            'id' => $note->getId(),
            'title' => $note->getTitle(),
            'content' => $note->getContent(),
            'color' => $note->getColor(),
            'positionX' => $note->getPositionX(),
            'positionY' => $note->getPositionY(),
            'createdAt' => $note->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $note->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
