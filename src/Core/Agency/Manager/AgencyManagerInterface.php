<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\Manager;

use Aurora\Core\Agency\Dto\AgencyInputInterface;
use Aurora\Core\Agency\Entity\AgencyInterface;

interface AgencyManagerInterface
{
    public function create(AgencyInputInterface $input): AgencyInterface;

    public function update(AgencyInterface $agency, AgencyInputInterface $input): void;

    public function delete(AgencyInterface $agency): void;
}
