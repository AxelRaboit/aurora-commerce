<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Service;

use Aurora\Module\PersonalFinance\Categorization\Repository\PersonalFinanceCategorizationRuleRepository;
use Aurora\Module\PersonalFinance\Categorization\Support\PatternNormalizer;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceCategorizationSuggestServiceInterface::class)]
class PersonalFinanceCategorizationSuggestService implements PersonalFinanceCategorizationSuggestServiceInterface
{
    public function __construct(
        protected readonly PersonalFinanceCategorizationRuleRepository $ruleRepository,
    ) {}

    public function suggest(CoreUserInterface $user, ?string $description): ?PersonalFinanceCategoryInterface
    {
        $pattern = PatternNormalizer::normalize($description);
        if (null === $pattern) {
            return null;
        }

        return $this->ruleRepository->findOneForUserByPattern($user, $pattern)?->getCategory();
    }

    /**
     * @param list<string> $descriptions
     *
     * @return array<string, PersonalFinanceCategoryInterface>
     */
    public function suggestBulk(CoreUserInterface $user, array $descriptions): array
    {
        $descriptionByPattern = [];
        foreach ($descriptions as $desc) {
            $pattern = PatternNormalizer::normalize($desc);
            if (null === $pattern) {
                continue;
            }

            $descriptionByPattern[$pattern] = $desc;
        }

        if ([] === $descriptionByPattern) {
            return [];
        }

        $rules = $this->ruleRepository->findForUserByPatterns($user, array_keys($descriptionByPattern));

        $out = [];
        foreach ($rules as $rule) {
            $original = $descriptionByPattern[$rule->getPattern()] ?? null;
            if (null !== $original) {
                $out[$original] = $rule->getCategory();
            }
        }

        return $out;
    }
}
