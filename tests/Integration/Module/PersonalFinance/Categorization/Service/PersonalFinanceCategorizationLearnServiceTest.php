<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Categorization\Service;

use Aurora\Module\PersonalFinance\Categorization\Repository\PersonalFinanceCategorizationRuleRepository;
use Aurora\Module\PersonalFinance\Categorization\Service\PersonalFinanceCategorizationLearnServiceInterface;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;

final class PersonalFinanceCategorizationLearnServiceTest extends PersonalFinanceTestCase
{
    private PersonalFinanceCategorizationLearnServiceInterface $service;
    private PersonalFinanceCategorizationRuleRepository $ruleRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(PersonalFinanceCategorizationLearnServiceInterface::class);
        $this->ruleRepository = $this->getService(PersonalFinanceCategorizationRuleRepository::class);
    }

    public function testLearnCreatesRuleWithNormalisedPattern(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Food');

        $this->service->learn($user, 'Boulangerie Léna', $cat);

        $rule = $this->ruleRepository->findOneForUserByPattern($user, 'boulangerie lena');
        self::assertNotNull($rule);
        self::assertSame('boulangerie lena', $rule->getPattern());
        self::assertSame($cat->getId(), $rule->getCategory()->getId());
        self::assertSame(1, $rule->getHits());
    }

    public function testLearnTwiceWithSameDescriptionBumpsHits(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Food');

        $this->service->learn($user, 'Cafe & Thé', $cat);
        $this->service->learn($user, 'Cafe & Thé', $cat);
        $this->service->learn($user, 'Cafe & Thé', $cat);

        $rule = $this->ruleRepository->findOneForUserByPattern($user, 'cafe & the');
        self::assertNotNull($rule);
        self::assertSame(3, $rule->getHits(), 'Hits should bump on duplicate learn');

        // Sanity: only one rule exists
        $all = $this->ruleRepository->findForUserByPatterns($user, ['cafe & the']);
        self::assertCount(1, $all);
    }

    public function testLearnSkipsSystemCategory(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $sysCat = $this->createCategory($wallet, 'Transfer', isSystem: true, systemKey: 'transfer_income');

        $this->service->learn($user, 'Salary deposit', $sysCat);

        $rule = $this->ruleRepository->findOneForUserByPattern($user, 'salary deposit');
        self::assertNull($rule);
    }

    public function testLearnSkipsNullDescription(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Food');

        $this->service->learn($user, null, $cat);

        $all = $this->ruleRepository->findForUserByPatterns($user, ['']);
        self::assertCount(0, $all);
    }

    public function testLearnSkipsEmptyDescription(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Food');

        $this->service->learn($user, '   ', $cat);

        // No rule for blank input — Pattern normaliser returns null on empty/whitespace
        $all = $this->ruleRepository->findPaginatedForUser($user, 1, 100);
        self::assertSame(0, $all['total']);
    }

    public function testLearnDescriptionNormalisationStripsAccentsAndLowercases(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W', '0.00');
        $cat = $this->createCategory($wallet, 'Food');

        $this->service->learn($user, '  Café   ÉTÉ  ', $cat);

        $rule = $this->ruleRepository->findOneForUserByPattern($user, 'cafe ete');
        self::assertNotNull($rule);
    }
}
