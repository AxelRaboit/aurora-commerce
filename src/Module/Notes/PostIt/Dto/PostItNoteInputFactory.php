<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PostItNoteInputFactoryInterface::class)]
class PostItNoteInputFactory implements PostItNoteInputFactoryInterface
{
    public function fromArray(array $data): PostItNoteInputInterface
    {
        return new PostItNoteInput(
            title: Str::trimOrNullFromArray($data, 'title'),
            content: $this->stringOrNull($data, 'content'),
            color: Str::trimOrNullFromArray($data, 'color'),
            positionX: isset($data['positionX']) ? (int) $data['positionX'] : null,
            positionY: isset($data['positionY']) ? (int) $data['positionY'] : null,
        );
    }

    /** @param array<string, mixed> $data */
    private function stringOrNull(array $data, string $key): ?string
    {
        if (!isset($data[$key])) {
            return null;
        }

        $value = $data[$key];

        if (!is_string($value) || '' === $value) {
            return null;
        }

        return $value;
    }
}
