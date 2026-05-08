<?php

declare(strict_types=1);

namespace Aurora\Core\Theme\Serializer;

use Aurora\Core\Theme\Entity\ThemeInterface;

interface ThemeSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ThemeInterface $theme): array;
}
