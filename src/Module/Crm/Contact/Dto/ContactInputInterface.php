<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Contact\Dto;

interface ContactInputInterface
{
    public function getFirstName(): string;

    public function getLastName(): string;

    public function getEmail(): ?string;

    public function getPhone(): ?string;

    public function getCompanyId(): ?int;

    public function getNotes(): ?string;
}
