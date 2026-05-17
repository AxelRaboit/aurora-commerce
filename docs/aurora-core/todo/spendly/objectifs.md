# PersonalFinance — Objectifs (Goals)

## Contexte

Objectif d'épargne personnel : "économiser 5000€ pour vacances avant
juillet 2027". Suivi visuel de la progression. Optionnellement lié à une
catégorie : si lié, le `savedAmount` est **auto-synchronisé** depuis la
somme des transactions de cette catégorie (via EventSubscriber).

Source Spendly :
- `app/Models/Goal.php`
- `app/Services/GoalService.php`
- `app/Http/Controllers/GoalController.php`
- `app/Observers/TransactionObserver.php` (logique de sync)
- `resources/js/Pages/Goals/Index.vue`

## Entité `PersonalFinanceGoal`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint, seq `seq_core_personal_finance_goal_id` | — |
| `user_id` | FK `core_user.id` | propriétaire |
| `wallet_id` | FK `core_personal_finance_wallet.id` nullable | nullable car goal peut être global (pas attaché à 1 wallet) |
| `category_id` | FK `core_personal_finance_category.id` nullable | si présent → auto-sync |
| `name` | string(120) | — |
| `targetAmount` | decimal(10,2) | — |
| `savedAmount` | decimal(10,2) default 0 | — |
| `deadline` | date nullable | — |
| `color` | string(7) nullable | hex `#RRGGBB` pour UI |
| `createdAt`, `updatedAt` | timestamps | — |

Méthodes domaine :
- `getProgress(): float` — `(savedAmount / targetAmount) * 100`, safe div by zero
- `isCompleted(): bool` — `savedAmount >= targetAmount`
- `isAutoTracked(): bool` — `category !== null`

## DTO + Manager + Serializer

Convention 5-couches standard.

### `PersonalFinanceGoalManager` — particularités

Méthodes spécialisées (multi-méthodes User-style — pas de `applyInput`
simple car la sémantique "deposit" est trop différente de "update") :

- `create(User, PersonalFinanceGoalInput): PersonalFinanceGoal`
- `update(PersonalFinanceGoal, PersonalFinanceGoalInput): PersonalFinanceGoal`
- `deposit(PersonalFinanceGoal, PersonalFinanceGoalDepositInput): PersonalFinanceGoal` :
  - Si `category` est set : refuse (le deposit ne sert à rien, c'est
    auto-sync) — ou alors crée une vraie transaction dans la catégorie
  - Sinon : incrémente `savedAmount` manuellement (pas de transaction
    sous-jacente)
- `delete(PersonalFinanceGoal): void`
- `recomputeSavedAmount(PersonalFinanceGoal): void` — pour les goals
  auto-trackés, recalcule depuis les transactions

DTO `PersonalFinanceGoalDepositInput(amount, wallet?, date?, description?)` —
distinct du DTO de create/update.

## EventSubscriber `PersonalFinanceGoalSyncSubscriber`

Réplique le `TransactionObserver` Spendly :

```php
#[AsEventListener(event: TransactionSavedEvent::class)]
#[AsEventListener(event: TransactionDeletedEvent::class)]
class PersonalFinanceGoalSyncSubscriber
{
    public function onTransactionSaved(TransactionSavedEvent $event): void
    {
        $tx = $event->transaction;
        if ($tx->getCategory() === null) return;

        $goals = $this->financeGoalRepository->findByCategory($tx->getCategory());
        foreach ($goals as $goal) {
            $this->financeGoalManager->recomputeSavedAmount($goal);
        }
    }

    public function onTransactionDeleted(TransactionDeletedEvent $event): void { /* idem */ }
}
```

Les `TransactionSavedEvent` / `DeletedEvent` doivent être dispatchés
depuis `PersonalFinanceTransactionManager` (audit hooks) — à formaliser dans la
fiche [`transactions.md`](transactions.md).

**Edge case** : si la catégorie d'une transaction change (était dans
goal A, passe dans goal B), recompute les **deux** goals. Dispatcher
inclut `previousCategory` dans l'event.

## Plan limits — pas portés

Spendly limite à 3 goals en Free / illimité en Pro. **Aurora n'implémente
pas ce gating** (cf. README §"exclu"). Si un client veut, il étend
`PersonalFinanceGoalManager::create()` et lève une exception.

## Vue

`assets/Module/PersonalFinance/backend/goal/GoalsApp.vue` :
- Grille de cards avec barre de progression colorée (color hex de l'entité)
- Tri : progression / deadline / amount (composable `usePersonalFinanceGoalSort`)
- Modale create/edit/deposit (3 actions distinctes — pas une modale
  unifiée car les champs diffèrent trop)
- Calcul UX : "il vous reste X mois → contribution mensuelle requise = Y€"

## Extensibilité

- Override `PersonalFinanceGoalManager::recomputeSavedAmount()` pour changer la
  formule (ex : pondérer certaines transactions)
- Override `PersonalFinanceGoalSyncSubscriber::onTransactionSaved()` pour skip
  certains cas (ex : exclure les transfers de l'auto-tracking)
- Slot Vue `extra-form-fields` : permet d'ajouter ex. un champ "priorité"
- Hook `protected createGoal()` standard pour substitution de classe

## Pointeurs

- Spendly : `Goal.php`, `GoalService.php`, `TransactionObserver.php`
- Aurora : pattern EventSubscriber avec auto-update : 
  `Aurora\Module\Project\EventSubscriber\TaskCountSubscriber`
