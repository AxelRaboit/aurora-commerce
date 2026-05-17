# PersonalFinance — Statistiques

## Contexte

Page analytique multi-charts pour analyser ses finances sur une période
configurable. Plus profond que la Vue globale (qui reste mensuelle) :
plusieurs métriques croisées, projection année, comparaison budget vs
réel.

Source Spendly :
- `app/Services/StatisticsService.php`
- `app/Http/Controllers/StatisticsController.php`
- `resources/js/Pages/Statistics/Index.vue`

## Données affichées

| Bloc | Type | Données |
|---|---|---|
| **Dépenses par catégorie** | Donut | Mois courant ou période sélectionnée, top N catégories, "Autres" pour le reste |
| **Évolution dépenses** | Bar chart | N derniers mois (3/6/12 sélectionnable) |
| **Taux d'épargne** | Line chart | Par mois : `(income - expense) / income * 100`, sur N mois |
| **Budget vs réel** | Bar groupé | Par section budget (Bills, Savings, etc.) : planifié vs actuel — seulement si wallets Budget |
| **Projection fin d'année** | Line + zone projetée | Trajectoire des dépenses YTD + projection linéaire jusqu'au 31/12 |
| **Tendances par catégorie** | Multi-line | Top 5 catégories sur N mois |

> **Pas de gating Pro/Free** comme dans Spendly. Toutes les périodes
> disponibles pour tous (cf. README §"exclu").

## Service `PersonalFinanceStatisticsService`

Service stateless, **pas de Manager** (pas de persistance).

Toutes les méthodes excluent les transactions de transfer
(`WHERE transfer_id IS NULL`) pour ne pas double-compter les flux
internes.

```php
class PersonalFinanceStatisticsService
{
    /** @return array<int, array{categoryId, name, total}> */
    public function byCategory(User $user, DateImmutable $start, DateImmutable $end): array;

    /** @return array<int, array{month, income, expense}> */
    public function byMonth(User $user, int $monthLimit = 6): array;

    /** @return array<int, array{month, income, expense, savingsRate}> */
    public function savingsRateHistory(User $user, int $monthLimit = 6): array;

    /** @return array{sections: array<...>, planned: float, actual: float} */
    public function budgetVsActual(User $user, DateImmutable $month): array;

    /** @return array{points: array<...>, projection: array<...>} */
    public function yearEndProjection(User $user): array;

    /** @return array{months: array<string>, categories: array<int, array{name, byMonth: array<float>}>} */
    public function byCategoryPerMonth(User $user, int $monthLimit = 6, int $topN = 5): array;

    public function currentMonth(User $user): array;   // KPI carte
    public function previousMonth(User $user): array;  // KPI carte
}
```

### Performance

Sur 12 mois × user avec ~500 transactions/mois : ~6000 transactions
chargées. Acceptable sans cache **si index `(user_id, date)` présent**.

Si dégradation observée :
- Ajouter cache applicatif (Redis) keyed sur `(user_id, statsName,
  periodHash)`, invalider sur `TransactionSavedEvent` (cf.
  [`objectifs.md`](objectifs.md) §EventSubscriber)
- TTL court (5min) suffit — l'utilisateur consulte rarement les stats en
  temps réel

## Controller

`Aurora\Core\Module\PersonalFinance\Statistics\Controller\Backend\PersonalFinanceStatisticsController` :

```php
#[Route('/backend/personal-finance/statistics')]
public function index(Request $request, PersonalFinanceStatisticsService $svc): Response
{
    $monthLimit = $request->query->getInt('period', 6);
    $user = $this->getUser();

    return $this->render('@PersonalFinance/statistics/index.html.twig', [
        'data' => [
            'byCategory'       => $svc->byCategory($user, ...),
            'byMonth'          => $svc->byMonth($user, $monthLimit),
            'savingsHistory'   => $svc->savingsRateHistory($user, $monthLimit),
            'budgetVsActual'   => $svc->budgetVsActual($user, new DateImmutable('first day of this month')),
            'projection'       => $svc->yearEndProjection($user),
            'byCategoryMonths' => $svc->byCategoryPerMonth($user, $monthLimit),
            'currentKpis'      => $svc->currentMonth($user),
            'previousKpis'     => $svc->previousMonth($user),
        ],
    ]);
}
```

## Vue

`assets/Module/PersonalFinance/backend/statistics/StatisticsApp.vue` :

- 2 KPI cards (current month, previous month, % delta)
- Sélecteur de période (3 / 6 / 12 mois) — pas de date picker custom en
  V1 pour rester simple
- 6 charts (cf. table ci-dessus), grille responsive
- Pas de filtre par wallet en V1 (tous wallets accessibles agrégés). Si
  demandé : ajouter dropdown wallet en haut, paramètre query `?wallet=`

Composables :
- `usePersonalFinanceChartOptions.js` (theme light/dark + options chart.js
  partagées entre Statistics, Dashboard, Overview)
- `usePersonalFinanceStatisticsFilters.js`

## Extensibilité

- Hook `protected aggregateExpensesByCategory(User, DateImmutable, DateImmutable): array`
  pour qu'un client puisse filtrer/transformer
- Slot Vue `extra-charts` après les charts standards
- Service entièrement décorable (`#[AsAlias(PersonalFinanceStatisticsServiceInterface::class)]`
  → un client peut substituer avec sa propre implémentation Redis-cached
  ou faire du materialized view)

> Tracer une **interface** `PersonalFinanceStatisticsServiceInterface` dès V1 —
> sans implémenter de double, mais pour que le client puisse decorate.

## Pointeurs

- Spendly : `StatisticsService.php` (à lire intégralement pour les
  requêtes exactes), `Statistics/Index.vue`
- Aurora : `Aurora\Module\Editorial\Statistics\Service\EditorialStatisticsService`
  pour le pattern global
