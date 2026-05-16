<?php

declare(strict_types=1);

namespace Aurora\Core\Twig;

use Aurora\Core\Migration\Service\MigrationStatusChecker;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes the pending-migrations count to Twig templates so the admin
 * layout can render a warning banner when the dev DB is behind. Wraps
 * {@see MigrationStatusChecker}.
 */
final class MigrationStatusExtension extends AbstractExtension
{
    public function __construct(private readonly MigrationStatusChecker $checker) {}

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('aurora_pending_migrations', fn (): int => $this->checker->countPending()),
        ];
    }
}
