<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Service\Manager;

use Aurora\Module\Platform\Service\Dto\ServiceInputInterface;
use Aurora\Module\Platform\Service\Entity\ServiceInterface;

interface ServiceManagerInterface
{
    public function create(ServiceInputInterface $input): ServiceInterface;

    public function update(ServiceInterface $service, ServiceInputInterface $input): void;

    public function delete(ServiceInterface $service): void;
}
