<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Company\Dto;

interface CompanyInputInterface
{
    public function getName(): string;

    public function getIndustry(): ?string;

    public function getWebsite(): ?string;

    public function getPhone(): ?string;

    public function getAddress(): ?string;

    public function getNotes(): ?string;
}
