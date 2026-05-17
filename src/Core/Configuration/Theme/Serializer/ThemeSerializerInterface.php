<?php

declare(strict_types=1);

namespace Aurora\Core\Configuration\Theme\Serializer;

use Aurora\Core\Configuration\Theme\Entity\ThemeInterface;

interface ThemeSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ThemeInterface $theme): array;
}
