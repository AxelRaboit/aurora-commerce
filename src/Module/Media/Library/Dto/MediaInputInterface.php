<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Dto;

interface MediaInputInterface
{
    public function getAlt(): ?string;

    public function getCaption(): ?string;

    public function getFocalX(): ?float;

    public function getFocalY(): ?float;

    public function getFolderId(): ?int;
}
