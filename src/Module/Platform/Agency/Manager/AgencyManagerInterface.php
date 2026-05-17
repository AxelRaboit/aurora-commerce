<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Agency\Manager;

use Aurora\Module\Platform\Agency\Dto\AgencyInputInterface;
use Aurora\Module\Platform\Agency\Entity\AgencyInterface;

interface AgencyManagerInterface
{
    public function create(AgencyInputInterface $input): AgencyInterface;

    public function update(AgencyInterface $agency, AgencyInputInterface $input): void;

    public function delete(AgencyInterface $agency): void;
}
