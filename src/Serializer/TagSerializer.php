<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Tag;
use DateTimeInterface;

final readonly class TagSerializer
{
    public function serialize(Tag $tag): array
    {
        return [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'slug' => $tag->getSlug(),
            'createdAt' => $tag->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
