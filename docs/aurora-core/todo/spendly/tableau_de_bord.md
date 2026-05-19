# PersonalFinance — Tableau de bord (Dashboard)

> Sous-module **agrégation** : pas d'entité propre. Service qui assemble
> des KPIs et des aperçus depuis `Wallet`, `Transaction`, `Goal`,
> `RecurringTransaction`, `BudgetItem`. Dépend de **tous** les autres
> sous-modules → à implémenter en dernier (ou stubbé en V0).

## Contexte

Page d'accueil du module PersonalFinance, accessible via la sidebar. Donne une
vue immédiate du mois en cours, sans navigation. Évite à l'utilisateur
d'aller chercher la même info wallet par wallet.

Source Spendly :
- `app/Http/Controllers/DashboardController.php`
- `app/Services/DashboardService.php` (à lire pour les requêtes
  d'agrégation exactes)
- `resources/js/Pages/Dashboard.vue`

## Données affichées (V1)

| Bloc | Données | Source |
|---|---|---|
| **KPI dépenses mois** | total dépenses mois courant, total mois précédent, delta % | `PersonalFinanceTransaction` filtré `type=Expense AND transfer_id IS NULL AND month=current` |
| **KPI revenus mois** | idem revenus | idem `type=Income` |
| **KPI wallets** | nombre de wallets accessibles, total balance combinée | `PersonalFinanceWalletRepository::findAccessibleByUser` |
| **Sparkline 30j** | total dépense par jour (line chart fill) | aggregation jour par jour, fill gaps avec 0 |
| **Top catégories** | top 5 catégories du mois par dépense, avec % | groupBy `category_id` |
| **Wallets pinned** | wallets avec `show_on_dashboard=true`, leur balance courante | `PersonalFinanceWalletRepository::findPinned` |
| **Dernières transactions** | 5–6 dernières transactions tous wallets accessibles | order by date desc, transfer_id IS NULL |
| **Objectifs actifs** | count des goals non atteints (saved < target) | `PersonalFinanceGoalRepository::countActive` |
| **Récurrentes à venir** | count des recurring `active=true` dont `day_of_month >= today.day` | `PersonalFinanceRecurringRepository::countUpcoming` |
| **Alertes budget** | items du budget courant où `actual > planned + carried_over` | dépend de `PersonalFinanceBudgetService::loadWithActuals` |

## Direction d'implémentation

### Service principal

`Aurora\Core\Module\PersonalFinance\Dashboard\Service\PersonalFinanceDashboardService`

Methode unique :
```php
public function snapshot(User $user, DateImmutable $reference = null): PersonalFinanceDashboardSnapshot
```

Retourne un **DTO read-only** `PersonalFinanceDashboardSnapshot` (un seul fichier,
pas de factory — c'est une vue, pas un input). Propriétés :
- `currentMonthExpense: string`
- `currentMonthIncome: string`
- `previousMonthExpense: string` (pour calculer delta côté Vue)
- `dailySparkline: array<string{date,amount}>`
- `topCategories: array<int, PersonalFinanceCategorySnapshot>` (5)
- `pinnedWallets: array<int, PersonalFinanceWalletSnapshot>`
- `recentTransactions: array<int, PersonalFinanceTransactionSnapshot>` (6)
- `activeGoalsCount: int`
- `upcomingRecurringCount: int`
- `budgetAlertsCount: int`

Optimisations :
- 1 seule traversée de `PersonalFinanceTransactionRepository::findForUserBetween($user, $monthStart, $monthEnd)` puis filtrage en PHP
- Préchargement (`addSelect`) des relations `category`, `wallet` sur les 6 transactions récentes
- Index DB sur `(user_id, date)` et `(wallet_id, date)` côté `core_personal_finance_transaction`

### Controller

`Aurora\Core\Module\PersonalFinance\Dashboard\Controller\Backend\PersonalFinanceDashboardController` :
- `GET /backend/personal-finance` → `dashboard()` → renvoie le snapshot sérialisé dans le template Twig qui monte `DashboardApp.vue`

### Vue

`src/Module/PersonalFinance/assets/backend/dashboard/DashboardApp.vue` :
- Grille responsive : 4 KPI cards en haut + sparkline pleine largeur + 3 colonnes (top categories / recent transactions / objectifs+récurrentes+alertes)
- Charts : Chart.js (déjà dispo dans Aurora — cf. usage Editorial stats)
- Polling/refresh : pas pour V1, l'utilisateur recharge

## Extensibilité

Une seule classe à étendre côté client : `PersonalFinanceDashboardService`. Hooks
`protected` à exposer (sans en faire trop, cf.
[`../../dev/entity_extensibility_convention.md` §7.bis](../../dev/entity_extensibility_convention.md)) :

- `protected loadTopCategories(User, DateImmutable $monthStart, DateImmutable $monthEnd): array`
- `protected loadRecentTransactions(User, int $limit): array`

Suffisant pour qu'un client puisse ajouter des blocs custom (ex: top
marchands, alertes seuil custom) en exposant son propre service `extends`
qui s'injecte dans la Vue via une prop `extraBlocks` slot-based.

## Pointeurs

- Spendly : `DashboardService.php`, `DashboardController.php`,
  `resources/js/Pages/Dashboard.vue`
- Aurora : pattern `<Module>StatisticsService` côté `Editorial` pour le
  shape du service d'agrégation
- Convention dashboard Vue : voir [`add_module.md`](../../dev/add_module.md)
  §"page d'accueil de module"
