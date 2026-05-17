<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Dto;

interface MessageInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): MessageInputInterface;
}
