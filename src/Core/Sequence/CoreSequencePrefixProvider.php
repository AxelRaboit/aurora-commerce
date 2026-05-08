<?php

declare(strict_types=1);

namespace Aurora\Core\Sequence;

final class CoreSequencePrefixProvider implements SequencePrefixProviderInterface
{
    public function values(): array
    {
        return array_column(SequencePrefixEnum::cases(), 'value');
    }

    public function name(): string
    {
        return 'Aurora Core';
    }
}
