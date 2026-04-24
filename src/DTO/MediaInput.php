<?php

declare(strict_types=1);

namespace App\DTO;

use App\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class MediaInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'media.errors.alt_required')]
        #[Assert\Length(max: 255)]
        public string $alt,
        public ?string $caption = null,
        #[Assert\Range(min: 0, max: 1, notInRangeMessage: 'media.errors.focal_out_of_range')]
        public ?float $focalX = null,
        #[Assert\Range(min: 0, max: 1, notInRangeMessage: 'media.errors.focal_out_of_range')]
        public ?float $focalY = null,
        public ?int $folderId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $focalX = $data['focalX'] ?? null;
        $focalY = $data['focalY'] ?? null;

        return new self(
            alt: Str::trimOrNull((string) ($data['alt'] ?? '')) ?? '',
            caption: Str::trimOrNull((string) ($data['caption'] ?? '')),
            focalX: null !== $focalX && '' !== $focalX ? (float) $focalX : null,
            focalY: null !== $focalY && '' !== $focalY ? (float) $focalY : null,
            folderId: isset($data['folderId']) && (int) $data['folderId'] > 0 ? (int) $data['folderId'] : null,
        );
    }
}
