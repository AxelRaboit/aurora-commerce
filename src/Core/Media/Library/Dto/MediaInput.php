<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Library\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class MediaInput implements MediaInputInterface
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public readonly ?string $alt = null,
        public readonly ?string $caption = null,
        #[Assert\Range(notInRangeMessage: 'media.errors.focal_out_of_range', min: 0, max: 1)]
        public readonly ?float $focalX = null,
        #[Assert\Range(notInRangeMessage: 'media.errors.focal_out_of_range', min: 0, max: 1)]
        public readonly ?float $focalY = null,
        public readonly ?int $folderId = null,
    ) {}

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function getFocalX(): ?float
    {
        return $this->focalX;
    }

    public function getFocalY(): ?float
    {
        return $this->focalY;
    }

    public function getFolderId(): ?int
    {
        return $this->folderId;
    }
}
