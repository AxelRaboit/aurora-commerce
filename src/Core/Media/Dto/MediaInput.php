<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class MediaInput
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $alt = null,
        public ?string $caption = null,
        #[Assert\Range(notInRangeMessage: 'media.errors.focal_out_of_range', min: 0, max: 1)]
        public ?float $focalX = null,
        #[Assert\Range(notInRangeMessage: 'media.errors.focal_out_of_range', min: 0, max: 1)]
        public ?float $focalY = null,
        public ?int $folderId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $focalX = $data['focalX'] ?? null;
        $focalY = $data['focalY'] ?? null;

        return new self(
            alt: Str::trimOrNullFromArray($data, 'alt'),
            caption: Str::trimOrNullFromArray($data, 'caption'),
            focalX: null !== $focalX && '' !== $focalX ? (float) $focalX : null,
            focalY: null !== $focalY && '' !== $focalY ? (float) $focalY : null,
            folderId: isset($data['folderId']) && (int) $data['folderId'] > 0 ? (int) $data['folderId'] : null,
        );
    }
}
