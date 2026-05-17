# PersonalFinance — Automatiques (Récurrentes + Planifiées)

> Entrée sidebar "Automatiques" regroupe **deux entités distinctes** dans
> Spendly : `RecurringTransaction` (mensuel) et `ScheduledTransaction`
> (one-off). Onglets côté UI.

## Contexte

- **Récurrentes** : transactions qui se reproduisent **chaque mois** à
  un jour fixe (1-28). Cas typique : loyer le 5, salaire le 28,
  abonnement Netflix le 15. Cycle continu jusqu'à désactivation.
- **Planifiées** : transactions **one-off** dans le futur — primes,
  taxes annuelles, voyages prévus. Marquées `isGenerated` une fois
  matérialisées en vraie transaction.

Source Spendly :
- `app/Models/{RecurringTransaction,ScheduledTransaction}.php`
- `app/Services/{RecurringTransactionService,ScheduledTransactionService}.php`
- `app/Http/Controllers/{RecurringTransactionController,ScheduledTransactionController}.php`
- `resources/js/Pages/Recurring/Index.vue`

## Entité `PersonalFinanceRecurringTransaction`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint, seq `seq_core_personal_finance_recurring_transaction_id` | — |
| `user_id` | FK `core_user.id` | — |
| `wallet_id` | FK `core_personal_finance_wallet.id` | cascade |
| `category_id` | FK `core_personal_finance_category.id` nullable | — |
| `type` | enum `PersonalFinanceTransactionType` | Income / Expense |
| `amount` | decimal(10,2) | — |
| `description` | string(255) nullable | — |
| `dayOfMonth` | smallint (1-28) | **pas 29-31** pour éviter les mois courts (constraint check) |
| `active` | bool default true | si false, skip génération |
| `lastGeneratedAt` | date nullable | format Y-m → empêche regénération même mois |
| `createdAt`, `updatedAt` | timestamps | — |

## Entité `PersonalFinanceScheduledTransaction`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint, seq `seq_core_personal_finance_scheduled_transaction_id` | — |
| `user_id` | FK | — |
| `wallet_id` | FK | cascade |
| `category_id` | FK nullable | — |
| `type` | enum | — |
| `amount` | decimal(10,2) | — |
| `description` | string(255) nullable | — |
| `scheduledDate` | date | — |
| `isGenerated` | bool default false | — |
| `createdAt`, `updatedAt` | timestamps | — |

> 2 entités séparées (pas de discriminator). Sémantique trop différente
> (frequency vs one-shot) et UX déjà séparée (onglets). Réutilisation
> via méthodes statiques partagées si besoin (`Aurora\...\Recurring\Support\AmountFormatter`).

## DTO + Manager + Serializer

Convention 5-couches standard pour chaque entité.

### `PersonalFinanceRecurringTransactionManager` — particularités

- `applyInput()` standard
- Hook spécifique `protected generateIfDue(PersonalFinanceRecurringTransaction): ?PersonalFinanceTransaction` :
  - Skip si `!active`
  - Skip si `lastGeneratedAt?->format('Y-m') === today.format('Y-m')`
  - Skip si `dayOfMonth > today.day`
  - Sinon : crée `PersonalFinanceTransaction` via `PersonalFinanceTransactionManager->create()`, met à jour `lastGeneratedAt`
- Méthode publique `toggle(PersonalFinanceRecurringTransaction): void` : flip
  `active`. Si on active et que le jour est passé pour ce mois, appelle
  `generateIfDue()` immédiatement.

### `PersonalFinanceScheduledTransactionManager` — particularités

- `applyInput()` standard
- Méthode `materialize(PersonalFinanceScheduledTransaction): PersonalFinanceTransaction` :
  - Crée la transaction
  - Marque `isGenerated = true`
  - Ne supprime PAS le `PersonalFinanceScheduledTransaction` — il sert d'historique

## Commande cron `personal-finance:recurring:generate`

```bash
php bin/console personal-finance:recurring:generate [--dry-run] [--date=YYYY-MM-DD]
```

Idempotente : peut tourner plusieurs fois par jour sans dupliquer (grâce
au check `lastGeneratedAt`).

```php
final class GenerateRecurringTransactionsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = $input->getOption('date')
            ? new DateImmutable($input->getOption('date'))
            : new DateImmutable('today');

        $candidates = $this->recurringRepository->findActiveDueOn($today);
        // findActiveDueOn = WHERE active=true AND day_of_month <= day(:today) AND (last_generated_at IS NULL OR YEAR_MONTH(last_generated_at) < YEAR_MONTH(:today))

        foreach ($candidates as $rec) {
            $tx = $this->recurringManager->generateIfDue($rec);
            // log + stats
        }
        return Command::SUCCESS;
    }
}
```

Cron suggéré : `0 3 * * *` (chaque jour à 3h00) — choix par client.

**Pas de scheduled-materialization automatique en V1.** Les transactions
planifiées restent manuelles : l'utilisateur les voit dans son tableau et
clique "matérialiser" quand elle se réalise. Pourquoi ? Parce que
contrairement aux récurrentes (gestes acquis), une transaction planifiée
peut être annulée ou modifiée à la dernière minute (prime annoncée à
3000€, payée 2500€). Pas d'automatisme.

> Si plus tard on veut auto-matérialiser : ajouter une option
> `autoMaterialize: bool` sur l'entité + étendre la commande pour les
> traiter aussi.

## Vue

`assets/Module/PersonalFinance/backend/recurring/RecurringApp.vue` :
- 2 onglets : "Récurrentes" / "Planifiées"
- Tab Récurrentes : table groupée par wallet, colonnes (description, montant, jour du mois, prochaine date estimée, toggle active)
- Tab Planifiées : table par date croissante, colonnes (description, montant, date prévue, statut, action "matérialiser")
- Modales create/edit pour chacune
- Action "matérialiser" → POST `/backend/personal-finance/scheduled/{id}/materialize`

Composables :
- `usePersonalFinanceRecurringForm.js`
- `usePersonalFinanceScheduledForm.js`

## Extensibilité

- Override `PersonalFinanceRecurringTransactionManager::generateIfDue()` pour
  changer la logique de génération (ex : règle "salaire = jour ouvré
  précédent si le jour fixé tombe un weekend")
- Override `materialize()` pour transformations custom (ex : appliquer
  une indexation)
- Slot Vue `extra-form-fields` sur les deux formulaires

## Pointeurs

- Spendly : 
  - `RecurringTransactionService::generateIfDue` (logique exact pour le port)
  - `app/Console/Commands/GenerateRecurring.php` (si présent — sinon créer)
- Aurora : 
  - Pattern commande idempotente : `bin/console aurora:billing:generate-invoices`
  - Pattern multi-onglets Vue : `EditorialPostsApp.vue` (drafts/published)
