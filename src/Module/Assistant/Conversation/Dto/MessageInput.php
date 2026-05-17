<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class MessageInput implements MessageInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'assistant.errors.message_empty')]
        #[Assert\Length(max: 100000)]
        public readonly string $content = '',
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }
}
