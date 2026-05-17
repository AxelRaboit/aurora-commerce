<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Vision\Contract;

interface VisionDescriberInterface
{
    /**
     * Send the image at the given absolute path to the configured vision
     * model and return a free-text description. Throws on transport/HTTP
     * errors so the caller (a tool) can wrap and translate to a user-
     * facing error string.
     */
    public function describe(string $imageAbsolutePath, string $prompt): string;

    public function getModel(): string;
}
