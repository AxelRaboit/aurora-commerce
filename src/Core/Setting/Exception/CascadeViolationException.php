<?php

declare(strict_types=1);

namespace Aurora\Core\Setting\Exception;

use DomainException;

/**
 * Thrown when an attempt is made to enable a parameter whose parent module
 * is disabled (e.g. enabling E-Commerce while ERP is off).
 */
final class CascadeViolationException extends DomainException
{
    public function __construct(public readonly string $childKey, public readonly string $parentKey)
    {
        parent::__construct(sprintf('Cannot enable "%s" while parent "%s" is disabled.', $childKey, $parentKey));
    }
}
