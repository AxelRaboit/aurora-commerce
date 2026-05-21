<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PostItNoteInput implements PostItNoteInputInterface
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $content = null,
        #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'notes.post_it.errors.invalid_color')]
        public readonly ?string $color = null,
        public readonly ?int $positionX = null,
        public readonly ?int $positionY = null,
    ) {}

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getPositionX(): ?int
    {
        return $this->positionX;
    }

    public function getPositionY(): ?int
    {
        return $this->positionY;
    }
}
