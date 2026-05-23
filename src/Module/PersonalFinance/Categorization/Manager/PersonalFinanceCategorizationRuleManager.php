<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Categorization\Entity\PersonalFinanceCategorizationRule;
use Aurora\Module\PersonalFinance\Categorization\Entity\PersonalFinanceCategorizationRuleInterface;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceCategorizationRuleManagerInterface::class)]
class PersonalFinanceCategorizationRuleManager implements PersonalFinanceCategorizationRuleManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(CoreUserInterface $user, string $pattern, PersonalFinanceCategoryInterface $category): PersonalFinanceCategorizationRuleInterface
    {
        $rule = $this->createRule();
        $rule->setUser($user);
        $rule->setPattern($pattern);
        $rule->setCategory($category);
        $rule->setHits(1);

        $this->entityManager->persist($rule);
        $this->entityManager->flush();

        $this->auditCreated($rule);

        return $rule;
    }

    public function updateCategory(PersonalFinanceCategorizationRuleInterface $rule, PersonalFinanceCategoryInterface $category): void
    {
        $rule->setCategory($category);
        $this->entityManager->flush();

        $this->auditUpdated($rule);
    }

    public function delete(PersonalFinanceCategorizationRuleInterface $rule): void
    {
        $this->auditDeleted($rule);
        $this->entityManager->remove($rule);
        $this->entityManager->flush();
    }

    /**
     * Hook: instantiate the concrete rule class.
     */
    protected function createRule(): PersonalFinanceCategorizationRuleInterface
    {
        return new PersonalFinanceCategorizationRule();
    }

    protected function auditCreated(PersonalFinanceCategorizationRuleInterface $rule): void
    {
        $this->auditLogger->log('personal_finance', 'categorization_rule.created', 'PersonalFinanceCategorizationRule', $rule->getId(), $this->auditPayload($rule));
    }

    protected function auditUpdated(PersonalFinanceCategorizationRuleInterface $rule): void
    {
        $this->auditLogger->log('personal_finance', 'categorization_rule.updated', 'PersonalFinanceCategorizationRule', $rule->getId(), $this->auditPayload($rule));
    }

    protected function auditDeleted(PersonalFinanceCategorizationRuleInterface $rule): void
    {
        $this->auditLogger->log('personal_finance', 'categorization_rule.deleted', 'PersonalFinanceCategorizationRule', $rule->getId(), $this->auditPayload($rule));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PersonalFinanceCategorizationRuleInterface $rule): array
    {
        return [
            'pattern' => $rule->getPattern(),
            'categoryId' => $rule->getCategory()->getId(),
            'hits' => $rule->getHits(),
        ];
    }
}
