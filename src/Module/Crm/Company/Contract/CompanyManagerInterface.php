<?php

declare(strict_types=1);

namespace App\Module\Crm\Company\Contract;

use App\Module\Crm\Company\DTO\CompanyInput;
use App\Module\Crm\Company\Entity\Company;

interface CompanyManagerInterface
{
    public function create(CompanyInput $input): Company;

    public function update(Company $company, CompanyInput $input): void;

    public function delete(Company $company): void;
}
