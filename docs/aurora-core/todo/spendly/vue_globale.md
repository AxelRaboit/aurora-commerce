# PersonalFinance — Vue globale (Overview)

> Sous-module **agrégation multi-wallet**, à mi-chemin entre Dashboard
> (vue rapide actuelle) et Statistiques (vue analytique profonde).
> Pas d'entité propre.

## Contexte

Vue synthétique multi-portefeuilles **avec navigation mensuelle**. Le
tableau de bord montre "maintenant" ; la vue globale montre "ce mois-là"
en agrégeant tous les wallets accessibles.

Source Spendly :
- `app/Http/Controllers/OverviewController.php`
- `resources/js/Pages/Overview/Index.vue`

## Données affichées

| Bloc | Données | Notes |
|---|---|---|
| **Navigation mensuelle** | mois précédent/suivant, dropdown rapide | URL `?month=2026-05` |
| **Sélecteur de période trend** | 3 / 6 / 12 mois | impacte les 2 charts ci-dessous |
| **KPI séparés Budget vs Simple** | total income + expense par mode de wallet | distinction utile car les wallets Simple sont souvent argent de poche, à ne pas mélanger avec le compte courant |
| **Bar chart income vs expenses par mois** | sur N mois (paramétrable) | 2 séries, axes Y partagés |
| **Donut catégories** | répartition des dépenses du mois sélectionné par catégorie | 8 couleurs max, "Autres" pour le reste |
| **Cash flow analysis** | par mois : income − expenses = net | tableau ou bar net |

## Direction d'implémentation

### Service

`Aurora\Core\Module\PersonalFinance\Overview\Service\PersonalFinanceOverviewService`

```php
public function snapshot(User $user, DateImmutable $month, int $trendMonths = 6): PersonalFinanceOverviewSnapshot
```

Compose :
- `MonthlyAggregate[]` (income, expenses, net) sur les `$trendMonths` derniers mois
- `CategoryBreakdown[]` (catégorie → total) sur le mois courant
- `ModeSplit{budgetIncome, budgetExpense, simpleIncome, simpleExpense}` sur le mois courant

Index DB requis : `(user_id, date)` et `(category_id, date)`. Si jamais
les agrégats deviennent lents (>500ms sur 12 mois), envisager une vue
matérialisée Postgres `mv_finance_monthly_aggregate` rafraîchie par
EventSubscriber sur Transaction.save/delete.

### Controller + Vue

- `GET /backend/personal-finance/overview?month=YYYY-MM&trend=6` → `PersonalFinanceOverviewController::index`
- `OverviewApp.vue` : 1 page, 2 sélecteurs (mois + période trend), 3 charts Chart.js, 1 tableau cash flow

## Différence avec Dashboard et Statistics

| Aspect | Dashboard | Vue globale | Statistiques |
|---|---|---|---|
| **Période** | Mois courant figé | Mois sélectionnable | Période sélectionnable (jusqu'à 12 mois) |
| **Profondeur** | KPIs + 6 dernières tx | Charts simples + KPIs par mode | Charts multiples + projections + budget vs actual |
| **Wallets** | Pinned + récents | Tous accessibles, agrégés | Tous, parfois filtrable |
| **Usage** | Coup d'œil quotidien | Bilan mensuel | Analyse périodique |

Garder les 3 — pas de redondance, public différent.

## Extensibilité

- Hook `protected aggregateMonth(User, DateImmutable $monthStart, DateImmutable $monthEnd): MonthlyAggregate`
  pour permettre à un client d'inclure d'autres types (ex : agrégat
  factures Aurora Billing pour les indépendants qui consolident leurs
  finances perso+pro)
- Slot Vue `extra-charts` après le donut catégories

## Pointeurs

- Spendly : `OverviewController.php`, `Overview/Index.vue`
- Aurora : pattern similaire dans `BillingDashboardService` (à créer si
  pas encore existant — voir la roadmap module)
