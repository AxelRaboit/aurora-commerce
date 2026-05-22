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

## Sessions à venir

| # | Session | Bloque ? | Prérequis |
|---|---|---|---|
| 2c-2 | UI Members modal Vue + page publique respond + email integration (Mailer + Twig) | non — UX nice-to-have | 2c |
| 4 | Transaction + TransferService atomique | **critique** — cœur applicatif, débloque tout le reste | 3 (Category) |
| 5 | WalletBalanceService (currentBalance, monthlyBalance, rollingStartBalance) + BalanceAdjustmentService | non | **4** (somme transactions) |
| 6 | Budget + BudgetItem + BudgetPreset + carry-over service | non | 4 |
| 7 | Goal + EventSubscriber qui sync `savedAmount` depuis transactions | non | 4 |
| 8 | RecurringTransaction + ScheduledTransaction + commande cron `personal-finance:recurring:generate` | non | 4 |
| 9 | CategorizationRule + Learn/Suggest services | non | 4 |
| 10 | Dashboard + Overview + Statistics services (agrégations) | non | toutes les précédentes (data) |
| 11 | Import Excel (2 steps : upload → preview → process) | non | 4 |

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
