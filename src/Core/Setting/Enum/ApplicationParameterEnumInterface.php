<?php

declare(strict_types=1);

namespace App\Core\Setting\Enum;

interface ApplicationParameterEnumInterface
{
    public function getKey(): string;

    public function getLabel(): string;

    public function getDescription(): string;

    public function getDefaultValue(): string;

    public function getType(): string;

    public function getGroup(): string;
}
