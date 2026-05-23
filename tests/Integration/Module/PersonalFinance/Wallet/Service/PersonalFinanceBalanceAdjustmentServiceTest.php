<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Wallet\Service;

use Aurora\Module\PersonalFinance\Category\Enum\PersonalFinanceSystemCategoryKeyEnum;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceBalanceAdjustmentInput;
use Aurora\Module\PersonalFinance\Wallet\Service\PersonalFinanceBalanceAdjustmentServiceInterface;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;
use DomainException;

final class PersonalFinanceBalanceAdjustmentServiceTest extends PersonalFinanceTestCase
{
    private PersonalFinanceBalanceAdjustmentServiceInterface $service;
    private PersonalFinanceCategoryRepository $categoryRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(PersonalFinanceBalanceAdjustmentServiceInterface::class);
        $this->categoryRepository = $this->getService(PersonalFinanceCategoryRepository::class);
    }

    public function testAdjustUpCreatesIncomeWithBalanceAdjustmentCategory(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '100.00');

        $input = new PersonalFinanceBalanceAdjustmentInput(
            newBalance: '250.00',
            date: new DateTimeImmutable('2026-03-15'),
        );

        $tx = $this->service->adjust($user, $wallet, $input);

        self::assertSame(PersonalFinanceTransactionTypeEnum::Income, $tx->getType());
        self::assertSame('150.00', $tx->getAmount());
        self::assertNotNull($tx->getCategory());
        self::assertTrue($tx->getCategory()->isSystem());
        self::assertSame(PersonalFinanceSystemCategoryKeyEnum::BalanceAdjustment->value, $tx->getCategory()->getSystemKey());
    }

    public function testAdjustDownCreatesExpenseWithBalanceAdjustmentCategory(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '500.00');

        $input = new PersonalFinanceBalanceAdjustmentInput(
            newBalance: '120.00',
            date: new DateTimeImmutable('2026-03-15'),
        );

        $tx = $this->service->adjust($user, $wallet, $input);

        self::assertSame(PersonalFinanceTransactionTypeEnum::Expense, $tx->getType());
        self::assertSame('380.00', $tx->getAmount());
        self::assertNotNull($tx->getCategory());
        self::assertTrue($tx->getCategory()->isSystem());
    }

    public function testAdjustWhenBalanceAlreadyMatchesThrowsDomainException(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '100.00');

        $input = new PersonalFinanceBalanceAdjustmentInput(
            newBalance: '100.00',
            date: new DateTimeImmutable('2026-03-15'),
        );

        $this->expectException(DomainException::class);
        $this->service->adjust($user, $wallet, $input);
    }

    public function testSystemCategoryIsCreatedOnceAndReused(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '100.00');

        $tx1 = $this->service->adjust($user, $wallet, new PersonalFinanceBalanceAdjustmentInput(
            newBalance: '200.00',
            date: new DateTimeImmutable('2026-03-15'),
        ));

        $tx2 = $this->service->adjust($user, $wallet, new PersonalFinanceBalanceAdjustmentInput(
            newBalance: '50.00',
            date: new DateTimeImmutable('2026-03-16'),
        ));

        self::assertSame($tx1->getCategory()->getId(), $tx2->getCategory()->getId(), 'System category should be reused');

        // Sanity: still only one balance_adjustment category exists for the wallet
        $existing = $this->categoryRepository->findSystemByKey($wallet, PersonalFinanceSystemCategoryKeyEnum::BalanceAdjustment->value);
        self::assertNotNull($existing);
        self::assertSame($tx1->getCategory()->getId(), $existing->getId());
    }

    public function testAdjustDefaultsDateToTodayAndUsesFallbackDescription(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');

        $tx = $this->service->adjust($user, $wallet, new PersonalFinanceBalanceAdjustmentInput(
            newBalance: '25.00',
            date: null,
        ));

        self::assertSame((new DateTimeImmutable('today'))->format('Y-m-d'), $tx->getDate()->format('Y-m-d'));
        self::assertNotNull($tx->getDescription());
        self::assertNotSame('', $tx->getDescription());
    }

    public function testAdjustPreservesCustomDescription(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');

        $tx = $this->service->adjust($user, $wallet, new PersonalFinanceBalanceAdjustmentInput(
            newBalance: '25.00',
            date: new DateTimeImmutable('2026-03-15'),
            description: 'Manual reconciliation',
        ));

        self::assertSame('Manual reconciliation', $tx->getDescription());
    }
}
