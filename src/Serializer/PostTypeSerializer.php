<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\PostType;

final readonly class PostTypeSerializer
{
    public function serialize(PostType $postType): array
    {
        return [
            'id' => $postType->getId(),
            'label' => $postType->getLabel(),
            'slug' => $postType->getSlug(),
        ];
    }
}
