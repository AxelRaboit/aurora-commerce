<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\DTO;

use Aurora\Module\Crm\Deal\Enum\DealStageEnum;

interface DealInputInterface
{
    public function getName(): string;

    public function getStage(): DealStageEnum;

    public function getValue(): ?string;

    public function getContactId(): ?int;

    public function getCompanyId(): ?int;

    public function getClosingDate(): ?string;

    public function getNotes(): ?string;
}
