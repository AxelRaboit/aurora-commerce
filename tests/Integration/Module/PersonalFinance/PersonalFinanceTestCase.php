<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance;

use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategory;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransaction;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWallet;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Enum\UserRoleEnum;
use Aurora\Tests\Integration\IntegrationTestCase;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Shared scaffolding for PersonalFinance integration tests. Each child
 * test starts from a clean PF slice (all wallets/categories/transactions
 * /goals/rules/recurring/scheduled rows from the previous test are
 * purged in setUp) on top of the AppFixtures baseline.
 */
abstract class PersonalFinanceTestCase extends IntegrationTestCase
{
    protected EntityManagerInterface $entityManager;

    /** @var array<string, int> Counter for unique test user emails */
    private static array $emailCounter = [];

    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Purge all PF data so each test starts clean. Order respects FK constraints.
        $tables = [
            'core_personal_finance_categorization_rule',
            'core_personal_finance_recurring_transaction',
            'core_personal_finance_scheduled_transaction',
            'core_personal_finance_budget_item',
            'core_personal_finance_budget',
            'core_personal_finance_goal',
            'core_personal_finance_transaction',
            'core_personal_finance_category',
            'core_personal_finance_wallet_member',
            'core_personal_finance_wallet_invitation',
            'core_personal_finance_wallet',
        ];
        $connection = $this->entityManager->getConnection();
        foreach ($tables as $table) {
            try {
                $connection->executeStatement('DELETE FROM '.$table);
            } catch (\Throwable) {
                // table may not exist on every test database — best-effort cleanup
            }
        }
        $this->entityManager->clear();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    protected function getService(string $class): object
    {
        return static::getContainer()->get($class);
    }

    protected function createTestUser(string $emailPrefix = 'pf-user'): CoreUserInterface
    {
        self::$emailCounter[$emailPrefix] = (self::$emailCounter[$emailPrefix] ?? 0) + 1;
        $unique = self::$emailCounter[$emailPrefix];

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail(sprintf('%s-%d-%d@aurora.test', $emailPrefix, $unique, random_int(1, 9_999_999)))
            ->setName('Test User '.$unique)
            ->setRoles([UserRoleEnum::Dev->value])
            ->setPassword($hasher->hashPassword($user, 'password'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createWallet(
        CoreUserInterface $user,
        string $name = 'Test Wallet',
        string $startBalance = '0.00',
    ): PersonalFinanceWalletInterface {
        $wallet = new PersonalFinanceWallet();
        $wallet->setOwner($user);
        $wallet->setName($name);
        $wallet->setStartBalance($startBalance);

        $this->entityManager->persist($wallet);
        $this->entityManager->flush();

        return $wallet;
    }

    protected function createCategory(
        PersonalFinanceWalletInterface $wallet,
        string $name = 'Test Category',
        bool $isSystem = false,
        ?string $systemKey = null,
    ): PersonalFinanceCategoryInterface {
        $category = new PersonalFinanceCategory();
        $category->setUser($wallet->getOwner());
        $category->setWallet($wallet);
        $category->setName($name);
        $category->setIsSystem($isSystem);
        $category->setSystemKey($systemKey);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    protected function createTransaction(
        PersonalFinanceWalletInterface $wallet,
        ?PersonalFinanceCategoryInterface $category,
        PersonalFinanceTransactionTypeEnum $type,
        string $amount,
        DateTimeImmutable $date,
        ?string $description = null,
        ?string $transferId = null,
        ?string $splitId = null,
    ): PersonalFinanceTransactionInterface {
        $tx = new PersonalFinanceTransaction();
        $tx->setUser($wallet->getOwner());
        $tx->setWallet($wallet);
        $tx->setCategory($category);
        $tx->setType($type);
        $tx->setAmount($amount);
        $tx->setDate($date);
        $tx->setDescription($description);
        $tx->setTransferId($transferId);
        $tx->setSplitId($splitId);

        $this->entityManager->persist($tx);
        $this->entityManager->flush();

        return $tx;
    }
}
