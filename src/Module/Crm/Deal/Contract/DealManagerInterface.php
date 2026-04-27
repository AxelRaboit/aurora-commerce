<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Contract;

use Aurora\Module\Crm\Deal\DTO\DealInput;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;

interface DealManagerInterface
{
    public function create(DealInput $input): Deal;

    public function update(Deal $deal, DealInput $input): void;

    public function changeStage(Deal $deal, DealStageEnum $stage): void;

    public function delete(Deal $deal): void;
}
