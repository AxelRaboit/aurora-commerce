<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\Dto;

interface PostItNoteInputInterface
{
    public function getTitle(): ?string;

    public function getContent(): ?string;

    public function getColor(): ?string;

    public function getPositionX(): ?int;

    public function getPositionY(): ?int;
}
