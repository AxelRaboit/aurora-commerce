<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Manager;

use Aurora\Module\Crm\Company\Dto\CompanyInputInterface;
use Aurora\Module\Crm\Company\Entity\CompanyInterface;

interface CompanyManagerInterface
{
    public function create(CompanyInputInterface $input): CompanyInterface;

    public function update(CompanyInterface $company, CompanyInputInterface $input): void;

    public function delete(CompanyInterface $company): void;
}
