<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Library\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MediaInputFactoryInterface::class)]
class MediaInputFactory implements MediaInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): MediaInputInterface
    {
        $focalX = $data['focalX'] ?? null;
        $focalY = $data['focalY'] ?? null;

        return new MediaInput(
            alt: Str::trimOrNullFromArray($data, 'alt'),
            caption: Str::trimOrNullFromArray($data, 'caption'),
            focalX: null !== $focalX && '' !== $focalX ? (float) $focalX : null,
            focalY: null !== $focalY && '' !== $focalY ? (float) $focalY : null,
            folderId: isset($data['folderId']) && (int) $data['folderId'] > 0 ? (int) $data['folderId'] : null,
        );
    }
}
