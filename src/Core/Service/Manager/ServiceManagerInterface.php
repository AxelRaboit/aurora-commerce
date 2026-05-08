<?php

declare(strict_types=1);

namespace Aurora\Core\Service\Manager;

use Aurora\Core\Service\Dto\ServiceInputInterface;
use Aurora\Core\Service\Entity\ServiceInterface;

interface ServiceManagerInterface
{
    public function create(ServiceInputInterface $input): ServiceInterface;

    public function update(ServiceInterface $service, ServiceInputInterface $input): void;

    public function delete(ServiceInterface $service): void;
}
