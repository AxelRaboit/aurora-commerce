# PersonalFinance — nouveau module Aurora

Port du projet [`Spendly`](https://github.com/AxelRaboit/spendly) (Laravel 13 +
Vue 3 + Inertia) vers un module Aurora Symfony, sur le même modèle que
le port `Onyx → Notes` (cf. [`../notes/README.md`](../notes/README.md)).

Spendly est une application de **gestion financière personnelle** :
portefeuilles multiples, budget mensuel, transactions, virements,
objectifs d'épargne, transactions récurrentes/planifiées, catégories
custom, auto-catégorisation par patterns, statistiques, import Excel.

## Nom du module — tranché

**`PersonalFinance`** (décision mai 2026). Choisi pour :

- Distinguer du module **`Billing`** existant (qui couvre la finance B2B :
  factures, OCR, tiers) — `PersonalFinance` est explicitement la gestion
  **perso** (wallets, budget mensuel, objectifs).
- Cohérence avec le pattern Aurora "nom de domaine fonctionnel" (`Editorial`,
  `Ecommerce`, `Notes`…) plutôt que nom de produit.

Implications concrètes :

- Folder : `src/Module/PersonalFinance/` et `assets/Module/PersonalFinance/`
- Namespace : `Aurora\Core\Module\PersonalFinance\`
- Entités préfixées : `PersonalFinanceWallet`, `PersonalFinanceTransaction`,
  `PersonalFinanceCategory`, … (pour éviter collisions avec `EditorialCategory`,
  `BillingInvoice`, etc.)
- DB tables : `core_personal_finance_*`
- Sequences : `seq_core_personal_finance_<entity>_id`
- Routes : `/backend/personal-finance/*`
- Twig namespace : `@PersonalFinance/`
- Translations : `translations/personal_finance.<locale>.yaml`
- Storage : `var/uploads/personal-finance/`
- Console command : `personal-finance:recurring:generate`

## Scope — inclus / exclu

> Demande utilisateur explicite (mai 2026) : pas d'Administration, pas de
> guide de démarrage, pas de tiers Free/Pro/Stripe.

### Inclus (10 sous-modules — un par entrée sidebar Spendly)

| Sous-module | Fichier | Statut |
|---|---|---|
| Tableau de bord | [`tableau_de_bord.md`](tableau_de_bord.md) | ⏳ |
| Vue globale | [`vue_globale.md`](vue_globale.md) | ⏳ |
| Portefeuilles | [`portefeuilles.md`](portefeuilles.md) | ⏳ |
| Transactions | [`transactions.md`](transactions.md) | ⏳ |
| Objectifs | [`objectifs.md`](objectifs.md) | ⏳ |
| Automatiques (récurrentes + planifiées) | [`automatiques.md`](automatiques.md) | ⏳ |
| Catégories | [`categories.md`](categories.md) | ⏳ |
| Auto-catégorisation | [`auto_categorisation.md`](auto_categorisation.md) | ⏳ |
| Statistiques | [`statistiques.md`](statistiques.md) | ⏳ |
| Importer | [`importer.md`](importer.md) | ⏳ |

### Exclu (à NE PAS porter)

- **Administration** (`/dev/dashboard` côté Spendly) — Aurora a déjà son propre
  `Module/Assistant` + back-office `Core` ; pas de duplication.
- **Plan Free/Pro & Stripe** (`PlanController`, `PlanService`) — Aurora ne fait
  pas de SaaS billing au niveau bundle. Les limites par tier (3 portefeuilles
  free, etc.) **ne sont pas portées** : chaque client définit ses propres
  quotas via setting ou voter custom s'il en a besoin.
- **Guide de démarrage / Tour** (`TourController`, `Tour.vue`) — pas porté.
  L'onboarding utilisateur sera défini par chaque client.
- **Démo seeder** (`make demo-seed`, `is_demo` columns) — si utile, à refaire
  en fixture standard Doctrine côté client. Pas de colonne `is_demo` sur
  les entités Aurora.

## Architecture cible

### Layout `src/Module/PersonalFinance/`

```
src/Module/PersonalFinance/
├── PersonalFinanceModule.php                # ModuleInterface impl
├── Wallet/                           # tout ce qui touche portefeuille
│   ├── Entity/                       # PersonalFinanceWallet (Interface+Abstract+concrete)
│   │                                 # PersonalFinanceWalletMember, PersonalFinanceWalletInvitation
│   ├── Dto/                          # 5-couches : InputInterface + Input + Factory*
│   ├── Manager/                      # PersonalFinanceWalletManager + Member/Invitation managers
│   ├── Repository/
│   ├── Serializer/
│   ├── Enum/                         # PersonalFinanceWalletMode, PersonalFinanceWalletRole
│   ├── Voter/                        # PersonalFinanceWalletVoter (Owner/Editor/Viewer)
│   └── Controller/Backend/
├── Transaction/                      # transactions + virements + splits + attachments
│   ├── Entity/                       # PersonalFinanceTransaction
│   ├── Dto/  Manager/  Serializer/  Repository/
│   ├── Service/                      # PersonalFinanceTransferService (deux transactions liées)
│   └── Controller/Backend/
├── Budget/                           # mode "budget" : Budget+BudgetItem+BudgetPreset
│   ├── Entity/                       # PersonalFinanceBudget, PersonalFinanceBudgetItem, PersonalFinanceBudgetPreset
│   ├── Dto/  Manager/  Serializer/  Repository/
│   ├── Enum/                         # PersonalFinanceBudgetSection
│   ├── Service/                      # PersonalFinanceBudgetCarryOverService, PersonalFinanceBudgetCopyService
│   └── Controller/Backend/
├── Category/                         # PersonalFinanceCategory (per-wallet, system/user)
│   ├── Entity/  Dto/  Manager/  Serializer/  Repository/
│   ├── Enum/                         # PersonalFinanceSystemCategoryKey
│   └── Controller/Backend/
├── Categorization/                   # PersonalFinanceCategorizationRule (auto-catégorisation)
│   ├── Entity/  Dto/  Manager/  Serializer/  Repository/
│   ├── Service/                      # PersonalFinanceCategorizationLearnService, PersonalFinanceCategorizationSuggestService
│   └── Controller/Backend/
├── Goal/                             # objectifs d'épargne
│   ├── Entity/  Dto/  Manager/  Serializer/  Repository/
│   ├── EventSubscriber/              # sync saved_amount depuis transactions (équivalent TransactionObserver)
│   └── Controller/Backend/
├── Recurring/                        # PersonalFinanceRecurringTransaction + PersonalFinanceScheduledTransaction
│   ├── Entity/  Dto/  Manager/  Serializer/  Repository/
│   ├── Command/                      # personal-finance:recurring:generate (cron)
│   ├── Service/                      # PersonalFinanceRecurringGenerationService
│   └── Controller/Backend/
├── Dashboard/                        # agrégation KPI page d'accueil
│   └── Service/                      # PersonalFinanceDashboardService
├── Overview/                         # vue globale multi-wallets
│   └── Service/                      # PersonalFinanceOverviewService
├── Statistics/                       # agrégations analytiques
│   └── Service/                      # PersonalFinanceStatisticsService
├── Import/                           # import Excel
│   ├── Service/                      # PersonalFinanceImportService (parse) + PersonalFinanceImportTemplateService
│   └── Controller/Backend/
└── translations/                     # personal_finance.fr.yaml, personal_finance.en.yaml
```

### Frontend Vue

```
assets/Module/PersonalFinance/backend/
├── dashboard/                        # DashboardApp.vue
├── overview/                         # OverviewApp.vue
├── wallet/
│   ├── WalletsApp.vue                # liste + create/edit + members + invitations
│   ├── simple/SimpleWalletApp.vue    # vue d'un wallet simple
│   └── budget/BudgetWalletApp.vue    # vue d'un wallet budget (mensuel)
├── transaction/                      # composant modal + page liste
├── goal/                             # GoalsApp.vue
├── recurring/                        # RecurringApp.vue (onglets recurring+scheduled)
├── category/                         # CategoriesApp.vue
├── categorization/                   # CategorizationRulesApp.vue
├── statistics/                       # StatisticsApp.vue
└── import/                           # ImportApp.vue (2 steps : upload → preview)
```

Chaque page suit la **convention 5-couches** Sylius-style
([`../../dev/entity_extensibility_convention.md`](../../dev/entity_extensibility_convention.md)) :
- Entité non-`final` + Interface + Abstract + sequence `seq_core_personal_finance_<entity>_id`
- DTO non-`final` + InputInterface + InputFactory (avec `#[AsAlias]`)
- Manager non-`final` + Interface + hooks `protected` + `#[AsAlias]`
- Serializer non-`final` + Interface + `#[AsAlias]`
- Vue avec props `extraFields` + slots `extra-headers`/`extra-cells`/`extra-form-fields`

## Décisions structurelles transverses

### 1. Multi-tenant : portefeuille appartient à un `User`, partagé via `PersonalFinanceWalletMember`

Reprise du modèle Spendly :
- `PersonalFinanceWallet.owner` = `User` (le créateur original ; aussi `WalletMember.role = Owner`)
- `PersonalFinanceWalletMember(wallet, user, role)` avec enum `Owner | Editor | Viewer`
- `PersonalFinanceWalletInvitation(wallet, email, token, role, expires_at, accepted_at, declined_at)` —
  flux d'invitation par email + token signé

Voter Symfony `PersonalFinanceWalletVoter` :
- `VIEW` → Owner|Editor|Viewer
- `EDIT_TRANSACTIONS` → Owner|Editor
- `MANAGE_MEMBERS` → Owner
- `DELETE` → Owner

### 2. Transferts entre portefeuilles : deux transactions liées par `transferId` (UUID)

Pas de 3e type de transaction. Une transfer = un `PersonalFinanceTransaction` Expense
dans le wallet source + un Income dans le wallet cible, partageant le même
`transferId` (UUID). Service dédié `PersonalFinanceTransferService.create/update/delete`
assure l'atomicité.

Catégories système auto-générées (cf. `SystemCategoryKey` Spendly) :
- `transfer_income` (sur le wallet receveur)
- `transfer_expense_{toWalletId}` (sur le wallet émetteur)

Filtrage standard dans les stats : `WHERE transfer_id IS NULL`.

### 3. Wallet modes : enum `PersonalFinanceWalletMode { Budget, Simple }`

Une seule entité `PersonalFinanceWallet` avec colonne `mode`. Pas de discriminator
Doctrine (trop rigide pour étendre). Les services qui ne s'appliquent qu'à
un mode (BudgetService, etc.) lèvent une `LogicException` si appelés sur un
wallet du mauvais mode — interface unique mais comportement gated.

### 4. Budget mensuel : `PersonalFinanceBudget(wallet, month)` unique sur `(wallet_id, month)`

Une `PersonalFinanceBudget` par wallet par mois. `month` = `DateImmutable` (premier
du mois). Items = `PersonalFinanceBudgetItem` avec section enum, planned_amount,
carried_over (rollover du mois précédent), category (optionnelle).

Calcul de carry-over : pré-calculé à la lecture (pas stocké en cache pour
éviter les désynchros). Si perf devient un problème, ajouter un cache
applicatif keyed sur (wallet_id, month).

### 5. Montants : `Decimal` Doctrine `decimal(10,2)` partout

Cohérent avec Spendly. Pas de centimes int — la perte de précision n'est
pas un enjeu en gestion perso (vs facturation B2B où ça compte).
Représentation côté Vue : `string` avec validation numérique stricte.

### 6. Storage attachments transactions : `var/uploads/personal-finance/transactions/`

Conformément à la [convention de storage Aurora](../../../.claude/memory/aurora-shared/convention_storage_var_uploads.md) :
- Pas dans le document root
- Servi via route `/uploads/{path}` ou route plus spécifique
  `/backend/personal-finance/transactions/{transaction}/attachment` pour ACL gating

### 7. Récurrences : commande `personal-finance:recurring:generate` lancée par cron

Pas de queue Laravel ici (Aurora n'utilise pas Messenger systématiquement).
Une commande Symfony idempotente lancée par cron tous les jours à 03:00,
qui itère sur les `PersonalFinanceRecurringTransaction.active=true` et appelle
`PersonalFinanceRecurringGenerationService::generateIfDue(...)`.

Si plus tard on a besoin de retry/dead-letter, basculer sur Messenger.
Pour V1 : KISS.

### 8. i18n : 4 langues (fr/en/de/es) — déjà disponibles dans Spendly

Reprendre les YAML existants (`/resources/lang/{fr,en,de,es}/`) et les
convertir au format Symfony Translator (`translations/personal_finance.<locale>.yaml`).
Sous-namespace par feature (`personal_finance.wallet.*`, `personal_finance.transaction.*`).

## Précédent à respecter — le port Onyx → Notes

Le port `Onyx → Notes` (sous-module Markdown 🟢 terminé) est notre
référence. À chaque décision, se demander "comment on a tranché pour
Notes ?" :

- ✅ Entités préfixées par le nom du module
- ✅ Conventions 5-couches dès le scaffolding
- ✅ Service métier dédié pour la logique non-CRUD (équivalent
  `MarkdownNoteImageService`)
- ✅ Voter Symfony pour ownership/sharing
- ✅ Templates Vue isolés sous `assets/Module/<Module>/`
- ✅ Composables Vue réutilisables (`useNoteTree.js`, `useNoteDragDrop.js`)
- ✅ Traductions sous `translations/<module>.<locale>.yaml`

## Ordre d'exécution recommandé

Pas de dépendance hard sauf la suivante (chaque étape construit sur la précédente) :

1. **Wallet + Members + Voter + Invitations** — sans wallet, rien d'autre
   n'a de sens. Permet déjà des tests de partage.
2. **Category + system categories transfer** — préalable aux transactions.
3. **Transaction + Transfer service** — cœur applicatif. Première vraie
   valeur utilisateur.
4. **Budget + BudgetItem + carry-over** — débloquer le mode Budget.
5. **Goal + sync via EventSubscriber** — quick win une fois transactions OK.
6. **Recurring + Scheduled + commande cron** — automatisation.
7. **Categorization rule + Learn/Suggest services** — UX nice-to-have.
8. **Dashboard + Overview + Statistics** — agrégations pures, viennent en
   dernier (dépendent de tout le reste pour avoir de la data).
9. **Import Excel** — feature autonome, peut être faite en dernier.

## Convention de mise à jour de ce TODO

- Une entrée par sous-module dans le tableau ci-dessus, statut ⏳ → 🟡 → 🟢
- À chaque sous-module terminé : commit atomique, statut 🟢 dans le tableau,
  contenu du fichier sous-module remplacé par "✅ Terminé, voir
  `src/Module/PersonalFinance/<Section>/`"
- Si une décision structurelle change → mettre à jour ce README + une mémoire
  `.claude/memory/aurora-core/decision_<topic>.md`
