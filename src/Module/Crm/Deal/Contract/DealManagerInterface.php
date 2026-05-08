<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Contract;

use Aurora\Module\Crm\Deal\DTO\DealInput;
use Aurora\Module\Crm\Deal\Entity\DealInterface;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;

interface DealManagerInterface
{
    public function create(DealInput $input): DealInterface;

    public function update(DealInterface $deal, DealInput $input): void;

    public function changeStage(DealInterface $deal, DealStageEnum $stage): void;

    public function delete(DealInterface $deal): void;
}
