<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Manager;

use Aurora\Module\Crm\Deal\Dto\DealInputInterface;
use Aurora\Module\Crm\Deal\Entity\DealInterface;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;

interface DealManagerInterface
{
    public function create(DealInputInterface $input): DealInterface;

    public function update(DealInterface $deal, DealInputInterface $input): void;

    public function changeStage(DealInterface $deal, DealStageEnum $stage): void;

    public function delete(DealInterface $deal): void;
}
