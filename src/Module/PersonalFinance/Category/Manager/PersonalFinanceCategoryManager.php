<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Category\Dto\PersonalFinanceCategoryInputInterface;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategory;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Category\Enum\PersonalFinanceSystemCategoryKeyEnum;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceCategoryManagerInterface::class)]
class PersonalFinanceCategoryManager implements PersonalFinanceCategoryManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly PersonalFinanceCategoryRepository $categoryRepository,
    ) {}

    public function create(CoreUserInterface $user, PersonalFinanceWalletInterface $wallet, PersonalFinanceCategoryInputInterface $input): PersonalFinanceCategoryInterface
    {
        $category = $this->createCategory();
        $category->setUser($user);
        $category->setWallet($wallet);
        $this->applyInput($category, $input);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->auditCreated($category);

        return $category;
    }

    public function update(PersonalFinanceCategoryInterface $category, PersonalFinanceCategoryInputInterface $input): void
    {
        if ($category->isSystem()) {
            throw new DomainException('System categories cannot be edited.');
        }

        $this->applyInput($category, $input);
        $this->entityManager->flush();

        $this->auditUpdated($category);
    }

    public function delete(PersonalFinanceCategoryInterface $category): void
    {
        if ($this->isProtectedFromDeletion($category)) {
            throw new DomainException('This category is protected from deletion.');
        }

        $this->auditDeleted($category);

        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function getOrCreateSystem(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceSystemCategoryKeyEnum|string $systemKey,
        string $defaultName,
    ): PersonalFinanceCategoryInterface {
        $keyValue = $systemKey instanceof PersonalFinanceSystemCategoryKeyEnum ? $systemKey->systemKey() : $systemKey;

        $existing = $this->categoryRepository->findSystemByKey($wallet, $keyValue);
        if ($existing instanceof PersonalFinanceCategoryInterface) {
            return $existing;
        }

        $category = $this->createCategory();
        $category->setUser($user);
        $category->setWallet($wallet);
        $category->setName($defaultName);
        $category->setIsSystem(true);
        $category->setSystemKey($keyValue);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->auditCreated($category);

        return $category;
    }

    protected function createCategory(): PersonalFinanceCategoryInterface
    {
        return new PersonalFinanceCategory();
    }

    protected function applyInput(PersonalFinanceCategoryInterface $category, PersonalFinanceCategoryInputInterface $input): void
    {
        $category->setName($input->getName());
    }

    /**
     * Hook for clients to add custom protection rules. Default: system
     * categories are protected.
     */
    protected function isProtectedFromDeletion(PersonalFinanceCategoryInterface $category): bool
    {
        return $category->isSystem();
    }

    protected function auditCreated(PersonalFinanceCategoryInterface $category): void
    {
        $this->auditLogger->log('personal_finance', 'category.created', 'PersonalFinanceCategory', $category->getId(), $this->auditPayload($category));
    }

    protected function auditUpdated(PersonalFinanceCategoryInterface $category): void
    {
        $this->auditLogger->log('personal_finance', 'category.updated', 'PersonalFinanceCategory', $category->getId(), $this->auditPayload($category));
    }

    protected function auditDeleted(PersonalFinanceCategoryInterface $category): void
    {
        $this->auditLogger->log('personal_finance', 'category.deleted', 'PersonalFinanceCategory', $category->getId(), $this->auditPayload($category));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PersonalFinanceCategoryInterface $category): array
    {
        return [
            'walletId' => $category->getWallet()->getId(),
            'name' => $category->getName(),
            'isSystem' => $category->isSystem(),
            'systemKey' => $category->getSystemKey(),
        ];
    }
}
