<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sub-DTO for a single block inside a {@see BlockNoteInput}. Blocks are
 * stored as a JSON column on the note (ordered list) — no per-block id,
 * no position field, identity is the index in the array.
 *
 * Kept final readonly on purpose: sub-DTOs are not extension points
 * (per Aurora convention). The extensible payload is the free-form
 * $data map keyed by block type.
 */
final readonly class BlockInput
{
    /** @param array<string, mixed> $data */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 32)]
        #[Assert\Regex('/^[a-zA-Z][a-zA-Z0-9_-]*$/', message: 'notes.block.errors.invalid_type')]
        public string $type,
        public array $data,
        /** Editor.js' opaque block id — preserved so the editor keeps stable identity across saves. */
        public ?string $id = null,
    ) {}
}
