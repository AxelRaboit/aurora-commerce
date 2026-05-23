<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Transaction\Transfer\Service;

use Aurora\Module\PersonalFinance\Category\Enum\PersonalFinanceSystemCategoryKeyEnum;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Transfer\Dto\PersonalFinanceTransferInput;
use Aurora\Module\PersonalFinance\Transaction\Transfer\Service\PersonalFinanceTransferServiceInterface;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;
use DomainException;

final class PersonalFinanceTransferServiceTest extends PersonalFinanceTestCase
{
    private PersonalFinanceTransferServiceInterface $service;
    private PersonalFinanceTransactionRepository $transactionRepository;
    private PersonalFinanceCategoryRepository $categoryRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(PersonalFinanceTransferServiceInterface::class);
        $this->transactionRepository = $this->getService(PersonalFinanceTransactionRepository::class);
        $this->categoryRepository = $this->getService(PersonalFinanceCategoryRepository::class);
    }

    public function testCreateTransferProducesTwoLegsSharingAUuid(): void
    {
        $user = $this->createTestUser();
        $from = $this->createWallet($user, 'From', '500.00');
        $to = $this->createWallet($user, 'To', '0.00');

        $input = new PersonalFinanceTransferInput(
            fromWalletId: (int) $from->getId(),
            toWalletId: (int) $to->getId(),
            amount: '120.00',
            date: new DateTimeImmutable('2026-03-15'),
        );

        $transferId = $this->service->create($user, $from, $to, $input);

        self::assertNotSame('', $transferId);

        $legs = $this->transactionRepository->findByTransferId($transferId);
        self::assertCount(2, $legs);

        $expense = null;
        $income = null;
        foreach ($legs as $leg) {
            if (PersonalFinanceTransactionTypeEnum::Expense === $leg->getType()) {
                $expense = $leg;
            } elseif (PersonalFinanceTransactionTypeEnum::Income === $leg->getType()) {
                $income = $leg;
            }
        }

        self::assertNotNull($expense);
        self::assertNotNull($income);
        self::assertSame($from->getId(), $expense->getWallet()->getId());
        self::assertSame($to->getId(), $income->getWallet()->getId());
        self::assertSame('120.00', $expense->getAmount());
        self::assertSame('120.00', $income->getAmount());
        self::assertSame($transferId, $expense->getTransferId());
        self::assertSame($transferId, $income->getTransferId());
    }

    public function testCreateTransferCreatesSystemCategoriesOnDemandAndReusesThem(): void
    {
        $user = $this->createTestUser();
        $from = $this->createWallet($user, 'From', '500.00');
        $to = $this->createWallet($user, 'To', '0.00');

        // 1st transfer
        $this->service->create($user, $from, $to, new PersonalFinanceTransferInput(
            fromWalletId: (int) $from->getId(),
            toWalletId: (int) $to->getId(),
            amount: '50.00',
            date: new DateTimeImmutable('2026-03-15'),
        ));

        $expenseCatKey = PersonalFinanceSystemCategoryKeyEnum::transferExpenseKey((int) $to->getId());
        $incomeCatKey = PersonalFinanceSystemCategoryKeyEnum::TransferIncome->value;

        $expenseCat1 = $this->categoryRepository->findSystemByKey($from, $expenseCatKey);
        $incomeCat1 = $this->categoryRepository->findSystemByKey($to, $incomeCatKey);
        self::assertNotNull($expenseCat1);
        self::assertNotNull($incomeCat1);
        self::assertTrue($expenseCat1->isSystem());
        self::assertTrue($incomeCat1->isSystem());

        // 2nd transfer with same pair must reuse same categories
        $this->service->create($user, $from, $to, new PersonalFinanceTransferInput(
            fromWalletId: (int) $from->getId(),
            toWalletId: (int) $to->getId(),
            amount: '25.00',
            date: new DateTimeImmutable('2026-03-16'),
        ));

        $expenseCat2 = $this->categoryRepository->findSystemByKey($from, $expenseCatKey);
        $incomeCat2 = $this->categoryRepository->findSystemByKey($to, $incomeCatKey);
        self::assertSame($expenseCat1->getId(), $expenseCat2->getId());
        self::assertSame($incomeCat1->getId(), $incomeCat2->getId());
    }

    public function testCreateTransferRejectsSameWallet(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '500.00');

        $input = new PersonalFinanceTransferInput(
            fromWalletId: (int) $wallet->getId(),
            toWalletId: (int) $wallet->getId(),
            amount: '50.00',
            date: new DateTimeImmutable('2026-03-15'),
        );

        $this->expectException(DomainException::class);
        $this->service->create($user, $wallet, $wallet, $input);
    }

    public function testUpdateTransferMutatesBothLegsAndIgnoresWalletSelectors(): void
    {
        $user = $this->createTestUser();
        $from = $this->createWallet($user, 'From', '500.00');
        $to = $this->createWallet($user, 'To', '0.00');
        $other = $this->createWallet($user, 'Other', '0.00');

        $transferId = $this->service->create($user, $from, $to, new PersonalFinanceTransferInput(
            fromWalletId: (int) $from->getId(),
            toWalletId: (int) $to->getId(),
            amount: '50.00',
            date: new DateTimeImmutable('2026-03-15'),
            description: 'initial',
        ));

        // Update amount/date/description; tweak walletIds — service must ignore them
        $this->service->update($transferId, new PersonalFinanceTransferInput(
            fromWalletId: (int) $other->getId(),
            toWalletId: (int) $other->getId() + 999,
            amount: '75.00',
            date: new DateTimeImmutable('2026-04-20'),
            description: 'updated',
        ));

        $this->entityManager->clear();
        $legs = $this->transactionRepository->findByTransferId($transferId);
        self::assertCount(2, $legs);

        foreach ($legs as $leg) {
            self::assertSame('75.00', $leg->getAmount());
            self::assertSame('2026-04-20', $leg->getDate()->format('Y-m-d'));
            self::assertSame('updated', $leg->getDescription());
        }

        // Wallets must remain the original ones
        $walletIds = array_map(static fn ($leg): ?int => $leg->getWallet()->getId(), $legs);
        sort($walletIds);
        $expected = [$from->getId(), $to->getId()];
        sort($expected);
        self::assertSame($expected, $walletIds);
    }

    public function testUpdateMissingTransferThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->service->update('00000000-0000-0000-0000-000000000000', new PersonalFinanceTransferInput(
            fromWalletId: 1,
            toWalletId: 2,
            amount: '10.00',
            date: new DateTimeImmutable('2026-03-15'),
        ));
    }

    public function testDeleteRemovesBothLegsAtomically(): void
    {
        $user = $this->createTestUser();
        $from = $this->createWallet($user, 'From', '500.00');
        $to = $this->createWallet($user, 'To', '0.00');

        $transferId = $this->service->create($user, $from, $to, new PersonalFinanceTransferInput(
            fromWalletId: (int) $from->getId(),
            toWalletId: (int) $to->getId(),
            amount: '50.00',
            date: new DateTimeImmutable('2026-03-15'),
        ));

        $this->service->delete($transferId);

        $legs = $this->transactionRepository->findByTransferId($transferId);
        self::assertCount(0, $legs);
    }

    public function testLoadPairRejectsMalformedTransferWithOnlyOneLeg(): void
    {
        $user = $this->createTestUser();
        $from = $this->createWallet($user, 'From', '500.00');
        $to = $this->createWallet($user, 'To', '0.00');

        // Manually create a stray single-leg transfer
        $this->createTransaction(
            $from,
            null,
            PersonalFinanceTransactionTypeEnum::Expense,
            '10.00',
            new DateTimeImmutable('2026-03-15'),
            'orphan',
            transferId: 'orphan-transfer-id-zzz',
        );

        $this->expectException(DomainException::class);
        $this->service->delete('orphan-transfer-id-zzz');
    }
}
