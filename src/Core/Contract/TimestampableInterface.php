<?php

declare(strict_types=1);

namespace Aurora\Core\Contract;

use DateTimeImmutable;

interface TimestampableInterface
{
    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;
}
