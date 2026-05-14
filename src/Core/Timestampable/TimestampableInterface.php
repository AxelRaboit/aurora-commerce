<?php

declare(strict_types=1);

namespace Aurora\Core\Timestampable;

use DateTimeImmutable;

interface TimestampableInterface
{
    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;
}
