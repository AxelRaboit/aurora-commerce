<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceGoalDepositInput implements PersonalFinanceGoalDepositInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'personal_finance.goals.errors.deposit_amount_required')]
        #[Assert\Regex(
            pattern: '/^\d{1,8}(\.\d{1,2})?$/',
            message: 'personal_finance.goals.errors.deposit_amount_format',
        )]
        #[Assert\GreaterThan(value: '0', message: 'personal_finance.goals.errors.deposit_amount_positive')]
        public readonly string $amount = '0.00',
    ) {}

    public function getAmount(): string
    {
        return $this->amount;
    }
}
