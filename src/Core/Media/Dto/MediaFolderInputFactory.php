<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MediaFolderInputFactoryInterface::class)]
class MediaFolderInputFactory implements MediaFolderInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): MediaFolderInputInterface
    {
        return new MediaFolderInput(
            name: Str::trimOrNull((string) ($data['name'] ?? '')) ?? '',
            parentId: isset($data['parentId']) && (int) $data['parentId'] > 0 ? (int) $data['parentId'] : null,
        );
    }
}
