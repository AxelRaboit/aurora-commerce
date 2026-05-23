<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Dto;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceGoalInput implements PersonalFinanceGoalInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'personal_finance.goals.errors.name_required')]
        #[Assert\Length(max: 120)]
        public readonly string $name = '',
        #[Assert\NotBlank(message: 'personal_finance.goals.errors.target_required')]
        #[Assert\Regex(
            pattern: '/^\d{1,8}(\.\d{1,2})?$/',
            message: 'personal_finance.goals.errors.target_format',
        )]
        #[Assert\GreaterThan(value: '0', message: 'personal_finance.goals.errors.target_positive')]
        public readonly string $targetAmount = '0.00',
        public readonly ?int $walletId = null,
        public readonly ?int $categoryId = null,
        public readonly ?DateTimeImmutable $deadline = null,
        #[Assert\Regex(
            pattern: '/^#[0-9a-fA-F]{6}$/',
            message: 'personal_finance.goals.errors.color_format',
        )]
        public readonly ?string $color = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getTargetAmount(): string
    {
        return $this->targetAmount;
    }

    public function getWalletId(): ?int
    {
        return $this->walletId;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getDeadline(): ?DateTimeImmutable
    {
        return $this->deadline;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }
}
