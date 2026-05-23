<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Transfer\Dto;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceTransferInput implements PersonalFinanceTransferInputInterface
{
    public function __construct(
        #[Assert\NotNull(message: 'personal_finance.transfers.errors.from_wallet_required')]
        #[Assert\Positive(message: 'personal_finance.transfers.errors.from_wallet_required')]
        public readonly ?int $fromWalletId = null,
        #[Assert\NotNull(message: 'personal_finance.transfers.errors.to_wallet_required')]
        #[Assert\Positive(message: 'personal_finance.transfers.errors.to_wallet_required')]
        #[Assert\NotEqualTo(propertyPath: 'fromWalletId', message: 'personal_finance.transfers.errors.same_wallet')]
        public readonly ?int $toWalletId = null,
        #[Assert\NotBlank(message: 'personal_finance.transfers.errors.amount_required')]
        #[Assert\Regex(
            pattern: '/^\d{1,8}(\.\d{1,2})?$/',
            message: 'personal_finance.transfers.errors.amount_format',
        )]
        #[Assert\GreaterThan(value: '0', message: 'personal_finance.transfers.errors.amount_positive')]
        public readonly string $amount = '0.00',
        #[Assert\NotNull(message: 'personal_finance.transfers.errors.date_required')]
        public readonly ?DateTimeImmutable $date = null,
        #[Assert\Length(max: 255)]
        public readonly ?string $description = null,
    ) {}

    public function getFromWalletId(): ?int
    {
        return $this->fromWalletId;
    }

    public function getToWalletId(): ?int
    {
        return $this->toWalletId;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date ?? new DateTimeImmutable('today');
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
