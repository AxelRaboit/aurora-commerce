---
name: decision-personal-finance-transfer-legs-guard
description: Les transactions marquées d'un transferId ne peuvent être éditées/supprimées que via PersonalFinanceTransferService — le Manager CRUD refuse ces opérations directement
metadata:
  type: project
---

# Règle

Une `PersonalFinanceTransaction` dont `transferId` est non-`null` est
une **jambe de virement** : elle appartient à un couple atomique
(`Expense` sur le wallet source + `Income` sur le wallet cible) qui doit
rester synchronisé.

Conséquence : `PersonalFinanceTransactionManager::update()` et
`::delete()` **refusent** explicitement une transaction de transfer
(via le hook `protected ensureNotTransferLeg()`), en levant
`DomainException`. Les opérations sur les transferts passent
**obligatoirement** par `PersonalFinanceTransferService::update()` /
`::delete($transferId)`, qui maintient les deux jambes cohérentes.

```php
// src/Module/PersonalFinance/Transaction/Manager/PersonalFinanceTransactionManager.php
protected function ensureNotTransferLeg(PersonalFinanceTransactionInterface $tx, string $action): void
{
    if (null !== $tx->getTransferId()) {
        throw new DomainException(sprintf(
            'Cannot %s a transfer transaction directly. Use PersonalFinanceTransferService instead.',
            $action,
        ));
    }
}
```

La création (`Manager::create`) n'est **pas** gardée — c'est le
TransferService qui appelle son propre flux d'instanciation
(`createTransaction()` interne) avec `setTransferId()` dès la
construction, donc une création par le Manager standard ne peut pas
résulter d'une transaction de transfer accidentellement.

## Why

1. **Invariant des virements** : un transfer = exactement 2 transactions
   partageant un UUID `transferId`. Éditer/supprimer une seule jambe
   laisserait l'autre orpheline (Income sans contrepartie Expense, ou
   l'inverse), faussant les soldes et les agrégats.
2. **Source unique de coordination** : le TransferService est le seul
   endroit qui sait comment muter les deux jambes ensemble (même amount,
   même date, même description, dans un `wrapInTransaction`).
3. **Détection précoce** : un Controller qui oublie la distinction est
   immédiatement bloqué par l'exception au moment du test manuel —
   pas en prod après corruption silencieuse des données.
4. **Convention Spendly portée** : Spendly contourne ce risque côté UI
   (l'éditeur de transactions n'expose pas les transferts), mais ne le
   garde pas côté Service. Aurora le formalise au niveau du Manager.

## How to apply

- Tout `Controller` qui fait `transactionManager->update($tx, $input)`
  ou `->delete($tx)` sur une transaction trouvée par ID doit accepter
  que ces opérations peuvent lever `DomainException` si l'utilisateur
  tape un ID de jambe de transfer. À gérer dans le handler HTTP
  (renvoyer 422 ou 409, ou rediriger vers l'endpoint Transfer).
- **Anti-pattern à éviter** : "force-éditer" une jambe de transfer en
  appelant `$tx->setAmount(...)` directement depuis un autre service
  pour contourner l'exception. Si le besoin métier existe (cas
  d'usage légitime), passer par TransferService ou ajouter une méthode
  dédiée sur celui-ci.
- **Cas limites** :
  - `Wallet::delete()` cascade en `ON DELETE CASCADE` sur les
    transactions → les deux jambes seront supprimées séparément par
    Doctrine (l'exception du Manager ne s'applique pas au cascade SQL).
    C'est acceptable : si on supprime un wallet entier, les transferts
    historiques liés sont effacés. À documenter côté UI (warning de
    confirmation).
  - `Category::delete()` met `category_id` à null sur les jambes
    (`ON DELETE SET NULL`) : ne casse pas le transfer (les 2 jambes
    restent appariées via `transferId`), mais perd la catégorisation
    système. La solution propre : ne pas exposer la suppression des
    catégories système via la UI (déjà gardé par `isProtectedFromDeletion`
    sur `PersonalFinanceCategoryManager`).

## Pointeurs

- Service : `src/Module/PersonalFinance/Transaction/Transfer/Service/PersonalFinanceTransferService.php`
- Controller : `src/Module/PersonalFinance/Transaction/Controller/Backend/PersonalFinanceTransfersController.php`
- Repo lookup : `PersonalFinanceTransactionRepository::findByTransferId(string)`
- Mémoires liées :
  - [[decision-personal-finance-wallet-voter-reuse]] — ACL gating sur les 2 wallets
  - [[decision-personal-finance-system-categories-lazy]] — création lazy des `transfer_income` / `transfer_expense_X`
