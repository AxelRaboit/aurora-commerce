<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Dto;

interface ContactTagInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ContactTagInputInterface;
}
