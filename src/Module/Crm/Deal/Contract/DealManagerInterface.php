<?php

declare(strict_types=1);

namespace App\Module\Crm\Deal\Contract;

use App\Module\Crm\Deal\DTO\DealInput;
use App\Module\Crm\Deal\Entity\Deal;
use App\Module\Crm\Deal\Enum\DealStageEnum;

interface DealManagerInterface
{
    public function create(DealInput $input): Deal;

    public function update(Deal $deal, DealInput $input): void;

    public function changeStage(Deal $deal, DealStageEnum $stage): void;

    public function delete(Deal $deal): void;
}
