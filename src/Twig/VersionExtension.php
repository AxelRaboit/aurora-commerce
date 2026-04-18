<?php

declare(strict_types=1);

namespace App\Twig;

use App\Enum\AppVersionEnum;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class VersionExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly string $projectDir) {}

    public function getGlobals(): array
    {
        $versionFile = sprintf('%s/VERSION', $this->projectDir);

        return [
            'appVersion' => file_exists($versionFile) ? mb_trim(file_get_contents($versionFile)) : AppVersionEnum::Dev->value,
        ];
    }
}
