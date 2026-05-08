<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Dto;

interface CompanyInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): CompanyInputInterface;
}
