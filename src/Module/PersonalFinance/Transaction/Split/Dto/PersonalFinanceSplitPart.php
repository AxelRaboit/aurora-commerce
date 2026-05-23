<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Split\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sub-DTO — one part of a split. Sub-DTOs stay final readonly and are
 * not instrumented for client extension (cf. entity_extensibility_convention.md
 * §"DTO racine vs sub-DTO"). To extend a split, override the parent
 * PersonalFinanceSplitInput.
 */
final readonly class PersonalFinanceSplitPart
{
    public function __construct(
        #[Assert\NotNull(message: 'personal_finance.splits.errors.category_required')]
        #[Assert\Positive(message: 'personal_finance.splits.errors.category_required')]
        public ?int $categoryId,
        #[Assert\NotBlank(message: 'personal_finance.splits.errors.amount_required')]
        #[Assert\Regex(
            pattern: '/^\d{1,8}(\.\d{1,2})?$/',
            message: 'personal_finance.splits.errors.amount_format',
        )]
        #[Assert\GreaterThan(value: '0', message: 'personal_finance.splits.errors.amount_positive')]
        public string $amount,
        #[Assert\Length(max: 255)]
        public ?string $description = null,
    ) {}
}
