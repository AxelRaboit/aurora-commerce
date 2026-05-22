---
name: decision-personal-finance-wallet-voter-reuse
description: Toutes les sous-entités du module PersonalFinance gatent leurs écritures via PersonalFinanceWalletVoter — pas de Voter par sub-module
metadata:
  type: project
---

# Règle

Toutes les opérations write sur les sous-entités du module `PersonalFinance`
(Transaction, Budget, Goal, RecurringTransaction, CategorizationRule, …)
**doivent être gatées par `PersonalFinanceWalletVoter` sur le wallet parent**,
pas par un voter ad hoc créé pour chaque sub-module.

5 attributs disponibles :
- `VIEW` — Member (Owner/Editor/Viewer)
- `EDIT_TRANSACTIONS` — Owner/Editor (write transactions, budget items, goals, etc.)
- `EDIT` — Owner only (modifier la config wallet : nom, mode, startBalance…)
- `MANAGE_MEMBERS` — Owner only (gérer membres et invitations)
- `DELETE` — Owner only (supprimer le wallet)

## Why

1. **Cohérence sémantique** : la "permission d'écrire dans le wallet" est
   un concept unifié, pas réparti par type d'opération. Un Editor peut
   créer transactions, budget items, goals, recurring — tout ce qui est
   du contenu du wallet. Sinon on aurait 5 voters disant la même chose.
2. **DRY pour le client** : un client qui veut étendre les rôles (ajouter
   un rôle "Accountant" par exemple) modifie un seul Voter, pas 5.
3. **Réutilisation déjà documentée** : le commentaire de classe sur
   `PersonalFinanceWalletVoter` explicite cette intention :
   *"Reused across all PersonalFinance sub-modules to gate write operations
   on the parent wallet."*

## How to apply

Dans tout futur Controller PersonalFinance (Transaction, Budget, Goal,
Recurring, etc.) :

```php
use Aurora\Module\PersonalFinance\Wallet\Security\PersonalFinanceWalletVoter;

#[Route('/backend/personal-finance/transactions/{id}/update', ...)]
public function update(int $id): JsonResponse
{
    $transaction = $this->transactionRepository->find($id);
    // ...
    $this->denyAccessUnlessGranted(
        PersonalFinanceWalletVoter::EDIT_TRANSACTIONS,
        $transaction->getWallet(),  // ← le Voter prend le Wallet parent
    );
    // ...
}
```

**Ne pas créer** `PersonalFinanceTransactionVoter`, `PersonalFinanceBudgetVoter`
etc. Si une opération spécifique nécessite un check plus fin (rare), faire
un `if` dans le Controller après le `denyAccessUnlessGranted` de base.

**Cas limites** :
- `BalanceAdjustment` (création de transaction d'ajustement) → utiliser
  `EDIT_TRANSACTIONS` (c'est une écriture de transaction).
- `TransferService` (transferts inter-wallets) → check `EDIT_TRANSACTIONS`
  sur **les deux wallets** (source ET cible).
- `Invitation accept/decline` (par token public) → pas gaté par le Voter
  (l'auth se fait via le token + user logué), c'est l'exception.

Voir le code de référence dans
`src/Module/PersonalFinance/Wallet/Security/PersonalFinanceWalletVoter.php`
et son usage actuel dans `PersonalFinanceCategoriesController`,
`PersonalFinanceWalletsController`, `PersonalFinanceWalletMembersController`,
`PersonalFinanceWalletInvitationsController`.
