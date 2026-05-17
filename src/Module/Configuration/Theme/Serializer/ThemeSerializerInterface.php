<?php

declare(strict_types=1);

namespace Aurora\Module\Configuration\Theme\Serializer;

use Aurora\Module\Configuration\Theme\Entity\ThemeInterface;

interface ThemeSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ThemeInterface $theme): array;
}
