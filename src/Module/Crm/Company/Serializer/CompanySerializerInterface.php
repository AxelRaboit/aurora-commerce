<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Serializer;

use Aurora\Module\Crm\Company\Entity\CompanyInterface;

interface CompanySerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(CompanyInterface $company): array;
}
