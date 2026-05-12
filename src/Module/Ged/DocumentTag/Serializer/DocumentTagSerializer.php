<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentTag\Serializer;

use Aurora\Module\Ged\DocumentTag\Entity\DocumentTagInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DocumentTagSerializerInterface::class)]
class DocumentTagSerializer implements DocumentTagSerializerInterface
{
    public function serialize(DocumentTagInterface $tag): array
    {
        return [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'color' => $tag->getColor(),
        ];
    }
}
