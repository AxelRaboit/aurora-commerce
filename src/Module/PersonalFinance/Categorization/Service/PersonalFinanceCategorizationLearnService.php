<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Service;

use Aurora\Module\PersonalFinance\Categorization\Entity\PersonalFinanceCategorizationRuleInterface;
use Aurora\Module\PersonalFinance\Categorization\Manager\PersonalFinanceCategorizationRuleManagerInterface;
use Aurora\Module\PersonalFinance\Categorization\Repository\PersonalFinanceCategorizationRuleRepository;
use Aurora\Module\PersonalFinance\Categorization\Support\PatternNormalizer;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceCategorizationLearnServiceInterface::class)]
class PersonalFinanceCategorizationLearnService implements PersonalFinanceCategorizationLearnServiceInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly PersonalFinanceCategorizationRuleRepository $ruleRepository,
        protected readonly PersonalFinanceCategorizationRuleManagerInterface $ruleManager,
    ) {}

    public function learn(CoreUserInterface $user, ?string $description, PersonalFinanceCategoryInterface $category): void
    {
        if ($category->isSystem()) {
            return;
        }

        $pattern = PatternNormalizer::normalize($description);
        if (null === $pattern) {
            return;
        }

        $existing = $this->ruleRepository->findOneForUserByPattern($user, $pattern);
        if ($existing instanceof PersonalFinanceCategorizationRuleInterface) {
            $existing->setCategory($category);
            $existing->incrementHits();
            $this->entityManager->flush();

            return;
        }

        $this->ruleManager->create($user, $pattern, $category);
    }
}
