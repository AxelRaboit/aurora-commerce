<?php

declare(strict_types=1);

namespace Aurora\Core\Twig;

use Aurora\Core\Dev\Prerequisite\DevPrerequisiteChecker;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes developer prerequisite warnings to Twig so the admin layout
 * can render banners when something is missing from the local environment
 * (PHP extension, Node.js, Ollama, models…).
 *
 * Only called in the `dev` environment — the function is cheap (caches
 * the Ollama HTTP check to a temp file with a 60 s TTL).
 */
final class DevPrerequisiteExtension extends AbstractExtension
{
    public function __construct(private readonly DevPrerequisiteChecker $checker) {}

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'aurora_dev_warnings',
                fn (): array => $this->checker->getWarnings(),
            ),
        ];
    }
}
