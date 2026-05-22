---
name: project-personal-finance-port-status
description: État du port Spendly → PersonalFinance — sessions complétées + ordre des sessions à venir avec dépendances bloquantes
metadata:
  type: project
---

# État du port Spendly → PersonalFinance

Suivi rolling de l'avancement du module `src/Module/PersonalFinance/`
(port de [Spendly](https://github.com/AxelRaboit/spendly)). À mettre à
jour à la fin de chaque session.

## Sessions complétées (au 2026-05-22)

| # | Session | Commit | Statut |
|---|---|---|---|
| 1 | Fondations module (`PersonalFinanceModule`, Context, settings tab, ModuleParameterEnum::PersonalFinanceBackend) | `4968d108` | 🟢 |
| 2a | Wallet entity (5-layer) + sub-feature `PersonalFinanceWallets` + Controller/Twig/Vue placeholder | `4a3318ff` | 🟢 |
| 2b | WalletMember + Voter `PersonalFinanceWalletVoter` (5 attrs) + auto-create owner Member | `b1f86a99` | 🟢 |
| 2c | WalletInvitation + member management (updateRole, removeMember) + 2 Controllers (invitations, members) | `4ec2de2b` | 🟢 |
| 3 | Category (5-layer) + `SystemCategoryKeyEnum` + sub-feature `PersonalFinanceCategories` | `e44f366d` | 🟢 |
| 4a | Transaction (5-layer, sans Splits/Attachments/Transfer) + Enum Income/Expense + sub-feature `PersonalFinanceTransactions` | `f667ba1b` | 🟢 |

## Sessions à venir

| # | Session | Bloque ? | Prérequis |
|---|---|---|---|
| 2c-2 | UI Members modal Vue + page publique respond + email integration (Mailer + Twig) | non — UX nice-to-have | 2c |
| 4b | TransferService atomique (2 transactions liées par transferId UUID) + endpoint dédié + `getOrCreateSystem` pour `transfer_income`/`transfer_expense_X` | non | 4a |
| 4c | TransactionSplit (N tx liées par splitId) — optionnel V2 | non | 4a |
| 4d | Transaction attachments (1 fichier par tx, route `/uploads/personal-finance/transactions/`) | non | 4a |
| 5 | WalletBalanceService (currentBalance, monthlyBalance, rollingStartBalance) + BalanceAdjustmentService | non | **4a** (somme transactions) |
| 6 | Budget + BudgetItem + BudgetPreset + carry-over service | non | 4a |
| 7 | Goal + EventSubscriber qui sync `savedAmount` depuis transactions | non | 4a |
| 8 | RecurringTransaction + ScheduledTransaction + commande cron `personal-finance:recurring:generate` | non | 4a |
| 9 | CategorizationRule + Learn/Suggest services + `afterSave` hook standardisé dans la convention | non | 4a |
| 10 | Dashboard + Overview + Statistics services (agrégations) | non | toutes les précédentes (data) |
| 11 | Import Excel (2 steps : upload → preview → process) | non | 4a |

## État de conformité au 2026-05-22 (post-audit)

Backend PHP **100% conforme** à la convention 5-layer Sylius-style.
Frontend Vue post-audit :

✅ **Résolu** :
- Slots `extra-headers` / `extra-cells` / `extra-form-fields` sur les 3 apps
- Prop `extraFields` propagée aux composables sur les 3 apps
- Composables `useXxxCreate` + `useXxxEdit` extraits pour Wallets,
  Categories ET Transactions (SFC thin partout)
- **Pagination serveur** + AppPagination + AppLoader sur Wallets
  (`useListPage` + backend `/list` JSON + `findPaginatedAccessibleByUser`
  dans le repo, pattern CompaniesApp)

⚠️ **Dette restante** :
- **[HIGH] Pagination serveur manquante sur Categories + Transactions** :
  les 2 apps utilisent encore un filter client-side `currentCategories`
  / `currentTransactions` au lieu de `useListPage`. À refactor sur le
  même modèle que Wallets : repo `findPaginated*`, ViewBuilder
  `buildListPayload(PaginationRequest)`, Controller endpoint `/list`,
  Vue `useListPage` + `AppPagination` + `AppLoader`. Le skill
  `add-crud-list-ui` documente le pattern.

## Why

Le scope total est ~50+ fichiers par sub-module donc impossible en une
seule session. Le découpage en sessions atomiques (1 commit = 1 livrable
cohérent) permet de faire des pauses propres entre dev sessions sans
laisser de code half-baked.

## How to apply

1. **Prochaine session** (par défaut) : Session 4 — Transaction. C'est le
   prérequis critique pour 5, 6, 7, 8, 9, 11. Lire d'abord
   `docs/aurora-core/todo/spendly/transactions.md`.
2. **Si besoin de finir Wallet d'abord** : Session 2c-2 (UI Members) est
   safe à insérer entre 3 et 4 mais n'est pas un blocker.
3. **Toujours updater ce fichier en fin de session** : ajouter une ligne
   dans la table "Sessions complétées" avec le sha du commit, et
   éventuellement retirer la session correspondante de "à venir".
4. **Migrations à appliquer après pull** : voir `migrations/Version*.php`
   créées dans chaque commit. La règle reste `make migrate` après pull.
5. **Voir [[decision-personal-finance-wallet-voter-reuse]]** pour le
   pattern Voter à utiliser dans Transaction et tous les sub-modules
   ultérieurs.
6. **Voir [[decision-personal-finance-system-categories-lazy]]** pour la
   création lazy des catégories système (utilisée par TransferService et
   BalanceAdjustmentService).
