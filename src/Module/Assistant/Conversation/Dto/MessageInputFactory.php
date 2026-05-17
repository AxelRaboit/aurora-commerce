<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MessageInputFactoryInterface::class)]
class MessageInputFactory implements MessageInputFactoryInterface
{
    public function fromArray(array $data): MessageInputInterface
    {
        return new MessageInput(
            content: Str::trimFromArray($data, 'content'),
        );
    }
}
