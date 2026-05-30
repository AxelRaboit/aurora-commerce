<?php

declare(strict_types=1);

namespace Aurora\Core\Content;

/**
 * Renders one module-contributed Editor.js block type to front-end HTML.
 *
 * Lives in core so the Editorial block renderer can delegate unknown block
 * types to other modules without importing them (e.g. Ecommerce's
 * `productGrid`). Implementations are auto-registered via the
 * `aurora.content_block_renderer` tag; an absent module simply means its block
 * type renders to nothing.
 */
interface BlockRendererInterface
{
    /** The Editor.js block `type` this renderer handles (e.g. 'productGrid'). */
    public function getType(): string;

    /**
     * @param array<string, mixed> $data the block's `data` payload
     */
    public function render(array $data, string $locale): string;
}
