<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class UniqueEmail extends Constraint
{
    public function __construct(
        public readonly bool $excludeSelf = false,
        public string $message = 'unique_email',
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
    }
}
