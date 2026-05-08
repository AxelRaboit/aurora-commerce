<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Contract;

use Aurora\Module\Crm\Company\Dto\CompanyInput;
use Aurora\Module\Crm\Company\Entity\Company;

interface CompanyManagerInterface
{
    public function create(CompanyInput $input): Company;

    public function update(Company $company, CompanyInput $input): void;

    public function delete(Company $company): void;
}
