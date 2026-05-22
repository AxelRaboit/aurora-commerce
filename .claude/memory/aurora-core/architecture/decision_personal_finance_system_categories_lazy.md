---
name: decision-personal-finance-system-categories-lazy
description: Les catégories système PersonalFinance (transfer_income, transfer_expense_X, balance_adjustment) sont créées lazy via getOrCreateSystem — jamais en seed/fixture
metadata:
  type: project
---

# Règle

Les **catégories système** du module PersonalFinance (`transfer_income`,
`transfer_expense_{toWalletId}`, `balance_adjustment`) ne sont **jamais
seedées** à la création du wallet. Elles sont créées **lazy** au premier
usage, via :

```php
PersonalFinanceCategoryManager::getOrCreateSystem(
    CoreUserInterface $user,
    PersonalFinanceWalletInterface $wallet,
    PersonalFinanceSystemCategoryKeyEnum|string $systemKey,
    string $defaultName,
): PersonalFinanceCategoryInterface
```

C'est idempotent : si la catégorie système existe déjà pour ce wallet+key,
elle est retournée. Sinon créée + persistée + flush + audit.

Les seuls appelants attendus :
- `PersonalFinanceTransferService` (Session 4) — appelle 2 fois lors d'un
  transfer : une fois sur le wallet source avec
  `transferExpenseKey($targetWalletId)`, une fois sur le wallet cible avec
  `TransferIncome`.
- `PersonalFinanceBalanceAdjustmentService` (Session 5) — appelle avec
  `BalanceAdjustment`.

## Why

1. **Pas de pollution UI** : les system categories sont marquées
   `isSystem=true` et filtrées par
   `PersonalFinanceCategoryRepository::findUserCategoriesByWallet()` — donc
   pas affichées dans la UI Categories. Les pré-créer obligerait à filtrer
   partout. Lazy = par défaut absent → simpler.
2. **`transfer_expense_{toWalletId}` est dynamique** : on ne connaît pas
   à l'avance les couples de wallets entre lesquels il y aura des
   transferts. Impossible de seeder à l'avance.
3. **Catégorie scope-wallet** : chaque wallet a son propre jeu de
   `transfer_income` et `balance_adjustment` — pas une global. Si on
   seedait, il faudrait répéter à chaque wallet créé.
4. **Idempotence garantie** : l'unique partial index
   `(wallet_id, system_key) WHERE system_key IS NOT NULL` empêche les
   doublons côté DB. Même en race condition, on a une garantie.

## How to apply

**Dans TransferService (Session 4)** :

```php
$expenseCategory = $this->categoryManager->getOrCreateSystem(
    $user,
    $sourceWallet,
    PersonalFinanceSystemCategoryKeyEnum::transferExpenseKey($targetWallet->getId()),
    sprintf('Transfer to %s', $targetWallet->getName()),
);

$incomeCategory = $this->categoryManager->getOrCreateSystem(
    $user,
    $targetWallet,
    PersonalFinanceSystemCategoryKeyEnum::TransferIncome,
    'Transfer received',
);
```

**Dans BalanceAdjustmentService (Session 5)** :

```php
$category = $this->categoryManager->getOrCreateSystem(
    $user,
    $wallet,
    PersonalFinanceSystemCategoryKeyEnum::BalanceAdjustment,
    'Balance adjustment',
);
```

**Pour exposer une UI sur ces catégories** (rare — typically pas
nécessaire) : passer par un endpoint spécialisé, jamais par
`findUserCategoriesByWallet`. Le hook `protected isProtectedFromDeletion`
empêche déjà la suppression accidentelle via la UI standard.

**Anti-pattern à éviter** :
- Pré-créer des system categories à la création du wallet
  (`PersonalFinanceWalletManager::create`). Coût : pollution + couplage
  inter-modules + besoin de connaître les wallet cibles à l'avance.
- Hardcoder les noms (`'Transfer received'`) : OK pour V1, mais à
  internationaliser via Translator avant prod (cf. TODO `transactions.md`).

Voir le code dans
`src/Module/PersonalFinance/Category/Manager/PersonalFinanceCategoryManager.php`
et l'enum
`src/Module/PersonalFinance/Category/Enum/PersonalFinanceSystemCategoryKeyEnum.php`.
