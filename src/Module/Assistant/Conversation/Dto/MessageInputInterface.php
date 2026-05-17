<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Dto;

interface MessageInputInterface
{
    public function getContent(): string;
}
