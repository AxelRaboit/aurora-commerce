<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Dto;

interface ContactInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ContactInputInterface;
}
