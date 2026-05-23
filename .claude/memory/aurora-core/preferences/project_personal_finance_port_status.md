---
name: project-personal-finance-port-status
description: État du port Spendly → PersonalFinance — V1 scellée (port complet + audit + tests + polish UX), V2 backlog priorisé
metadata:
  type: project
---

# État du port Spendly → PersonalFinance

Suivi rolling de l'avancement du module `src/Module/PersonalFinance/`
(port de [Spendly](https://github.com/AxelRaboit/spendly)). À mettre à
jour à la fin de chaque session.

## V1 — Scellée le 2026-05-23

🟢 **Module 100% conforme aux conventions Aurora, prêt pour utilisateurs réels.**

### Sessions de port (1 → 10) — fait

| # | Session | Commit |
|---|---|---|
| 1 | Fondations module + Context + settings + ModuleParameterEnum | `4968d108` |
| 2a | Wallet entity 5-layer + sub-feature | `4a3318ff` |
| 2b | WalletMember + Voter (5 attrs) + auto-create owner Member | `b1f86a99` |
| 2c | WalletInvitation + member management Controllers | `4ec2de2b` |
| 3 | Category 5-layer + SystemCategoryKeyEnum | `e44f366d` |
| 4a | Transaction 5-layer (sans Splits/Attachments/Transfer) | `f667ba1b` |
| 4b | TransferService atomique (2 tx liées par transferId UUID v7) | `dce46700` |
| 4b-UI | Modale Transfer Vue + composables `useTransfersForm` / `useTransfersDelete` | `598d8418` |
| 4c | SplitService (N tx liées par splitId) + UI modale dynamique N rows | (inclus 4c batch) |
| 4d | Attachments transactions (1 fichier, PDF+raster, 5 Mo) | (inclus 4d batch) |
| 5 | WalletBalanceService + BalanceAdjustmentService + UI soldes | `73480834` |
| 6a | Budget + BudgetItem entities 5-layer + Manager.ensureForMonth lazy | `d49767e4` |
| 6b | Page Vue `PersonalFinanceBudgetsApp` + ViewBuilder | `b4892526` |
| 7a | Goal entity + Manager + `PersonalFinanceGoalSyncSubscriber` + 2 events | `aeac5162` |
| 7b | Page Vue `PersonalFinanceGoalsApp` (cards + progress + 3 modales) | `56ad3b02` |
| 8 | Recurring + Scheduled entities + cron command + Vue 2-tabs | `77f3620d` |
| 9 | CategorizationRule + LearnService + SuggestService + Subscriber | `3262f160` |
| 10 | PersonalFinanceDashboardService + Vue page + inline SVG sparkline | (commit batch) |

### Post-port (audit + polish + tests) — fait

| Lot | Commit | Contenu |
|---|---|---|
| Demo fixtures | `7dc973a2`, `701a68ef` | Données seed sur `dev@aurora.app` |
| Recurring polish | `79acbe30` | Format date, header colonne propre |
| Transaction polish | `628c6a93`, `ba8bf601`, `f38ebc7f` | Badges transfer/split/plain |
| Budgets — quick-add | `5b2f38fd` | Bouton Receipt sur chaque ligne → modal pré-remplie |
| Budgets — drill-down | `febb4089`, `b8867246`, `47d4b63b` | Modal liste transactions par item + infinite scroll + search |
| Bills → FixedCharges rename | `2391259c` | Enum + migration + i18n FR/EN |
| Help banners | `8918304f`, `677b95df` | AppMessage sur chaque page (8 pages) |
| Tone convention | `1102473c` | Réécriture impersonnelle + memory `convention_ui_copy_tone` |
| Recurring confirmation modals | `fd0291c5`, `ff5ad9c4` | Matérialiser + Mettre en pause |
| Switcher mois Budget | `fcfff510` | YYYY-MM → "Mai 2026" via `formatMonthYear` |
| Budget visual pass | `4c7a2385`, `406046d5`, `4d7f6b08` | Couleurs sections, icônes, progress, mobile parity, overrun, badges |
| Sidebar icons | `893205d5` | Fix fallback FileText + meilleurs choix |
| **Audit punch-list (6 items)** | `4e11e5ee`, `c979f02d` | Goal hook rename, extraFields 5 composables, DTOs WalletInvitation + CategorizationRule, decoupling Media, AppTab |
| Form labels | `c2c9e127` | Drop "(optionnel)" + extension `convention_form_components` |
| **Test coverage critique** | `845acdb2` | 11 fichiers, 65 tests, 180 assertions (TransferService, SplitService, GoalSyncSubscriber, LearnService, WalletBalanceService, BalanceAdjustmentService, Recurring/Scheduled managers, repo finders, PatternNormalizer) |

### Tests

- **2832 tests / 10801 assertions / 0 fail** (PHPUnit)
- **65 tests PF dédiés** (sur les 2832 totaux)
- 9 tests vitest `useDateFormat` (incl. `formatMonthYear`)

### Conventions ajoutées par la V1

- `convention_form_components` (étendue : asterisk = required, DTO ↔ UI miroir, `:error` bindé, placeholder obligatoire, dates AppDatePicker)
- `convention_ui_copy_tone` (nouvelle : impersonnel, pas de nom de marque, pas d'emojis dans le copy)
- `convention_multi_button_toolbar` (nouvelle : 2+ boutons `#actions` = wrapper `flex flex-col sm:flex-row`)

## V2 — Backlog priorisé

7 sessions identifiées. Aucune n'est bloquante pour les autres — l'ordre
ci-dessous reflète **valeur utilisateur** / effort.

### V2 — Sessions complétées

| # | Session | Commit | Statut |
|---|---|---|---|
| v2-3 | UI Members modal + email integration + public acceptance page | (lot v2-3) | 🟢 |
| v2-6 | Tags UI : input + display pills + click-to-filter + backend JSONB filter | (lot v2-6) | 🟢 |
| v2-1A | Budget auto-rollover : `repeatNextMonth` items copiés mois N → N+1 via `BudgetRolloverService` + toast UI | (lot v2-1A) | 🟢 |

**v2-3 livré** :
- Backend `GET /wallets/{walletId}/members` returning `{ members, invitations }` (voter `MANAGE_MEMBERS`)
- `WalletMemberSerializer` enrichi (`userName` + `userEmail` pour la modal)
- `PersonalFinanceWalletInvitationNotificationService` (Notification/, readonly non-final, hook overridable) — `MailService` + UrlGenerator
- `PersonalFinanceWalletInvitationManager.send/resend` déclenchent le mail post-persist + audit
- `Controller/Public/PersonalFinanceWalletInvitationAcceptController` : 3 routes publiques (show GET + accept POST + decline POST) sous `IS_AUTHENTICATED_FULLY` (token = authz)
- Vue `PersonalFinanceWalletMembersModal` + composable `useWalletMembers` (3 sections : actifs / pending / invite-form)
- Bouton `Users` (sky) sur chaque card wallet ouvre la modal
- Twig public `@PersonalFinance/public/wallet_invitation_accept.html.twig` (5 states : invalid / pending / accepted / declined / expired)
- Email template `@PersonalFinance/email/wallet_invitation.html.twig` extend shared base layout
- 30+ clés i18n FR + EN (members.* / invitations.* / mail.invitation.*)
- 7 nouveaux tests `PersonalFinanceWalletInvitationManagerTest` (send/accept/decline/resend lifecycle + reject Owner + dup pending)
- **Suite PF : 65 tests / 187 assertions verts**

### 🟢 Backlog cartographié (5 sessions)

| # | Session | Effort | Pourquoi |
|---|---|---|---|
| **v2-1B** | **BudgetPreset standalone** (Phase B de l'item v2-1) : entité template user-level + CRUD + apply-to-month. La Phase A (auto-rollover) est déjà livrée — Phase B couvre les bascules vers un autre profil de dépenses (ex. "Budget vacances" appliqué juste au mois de juillet). | M | Permet de créer un "Mois type" réutilisable indépendamment du dernier budget enregistré. |
| **v2-2** (11) | **Import Excel** : 2-step upload → preview → process. Service `PersonalFinanceImportService` (parse via PhpSpreadsheet ou ext locale) + template Excel téléchargeable + DTO de validation. Mapping flexible (date / montant / catégorie / description / tags) | L | Onboarding utilisateurs qui ont déjà un historique ailleurs (banque, autre app) |
| ~~v2-3~~ | ~~UI Members modal + email + public page~~ | ~~S~~ | ✅ **livré** — voir bloc ci-dessus |
| **v2-4** | **Vue Globale (Overview)** multi-wallets agrégée : `PersonalFinanceOverviewService` somme cross-wallet + Vue dédiée. Différent du Dashboard qui est centré KPIs du mois | M | Utilisateurs avec 3+ wallets : voir le big picture |
| **v2-5** | **Statistics page** : analyses temporelles (3/6/12 mois), heatmap dépenses, comparaison année-N vs N-1, breakdown par catégorie. Inline SVG chart lib-free comme le Dashboard | M | Power users qui veulent challenger leurs habitudes |

### 🟢 Quick-wins (2 sessions)

| # | Session | Effort | Pourquoi |
|---|---|---|---|
| ~~v2-6~~ | ~~Tags UI~~ | ~~S~~ | ✅ **livré** — voir bloc ci-dessus |
| **v2-7** | **Export PDF/Excel** des transactions + budget : bouton download dans la toolbar, génération côté serveur (PhpSpreadsheet déjà installable, Dompdf déjà dans Aurora) | M | Archivage, déclarations fiscales, transition vers comptable |

### 🔵 Hors scope V2 (idées sans décision)

- **Open Banking sync** (PSD2 / Bridge / Powens) — gros chantier 2-3 semaines, sécurité critique
- **Sous-catégories hiérarchiques** (Alimentation > Courses > Bio) — refacto Category + impact auto-cat + budget + dashboard
- **Multi-devise par wallet** (actuellement EUR global) — refacto Decimal handling
- **Budget annuel** (en complément du mensuel) — nouvelle entité, nouvelle page
- **Récurrences flexibles** (hebdo, annuel, quinzaine) — actuellement mensuel jour 1-28 uniquement
- **Notification d'alerte budget** (email/push quand actual > expected × threshold)

## Ordre recommandé pour V2

Si aucun item n'est prioritaire produit, attaquer par effort croissant
et valeur immédiate :

1. ~~v2-3 UI Members~~ ✅ **livré**
2. ~~v2-6 Tags UI~~ ✅ **livré**
3. ~~v2-1A Auto-rollover~~ ✅ **livré**
4. **v2-4 Overview** (M) — bénéficie de la maturité Budget/Goal (prochain — Phase B de v2-1 reportée car valeur marginale derrière l'auto-rollover)
5. **v2-5 Statistics** (M) — vient après Overview, même pattern d'agrégation
6. **v2-7 Export** (M) — feature transverse, peut se faire à n'importe quel moment
7. **v2-1B BudgetPreset standalone** (M) — rebascule entre profils (utile si Phase A ne suffit pas en pratique)
8. **v2-2 Import Excel** (L) — gros chantier, valeur d'onboarding moindre une fois la base bossée

## Comment l'appliquer

- À chaque session V2 terminée : ajouter une ligne dans une nouvelle
  table "V2 — Sessions complétées" + cocher l'item correspondant dans
  le backlog.
- Toujours commit atomique par session.
- Update le status à la fin de chaque session pour qu'une nouvelle
  conversation reprenne le contexte sans lire le code.
- Voir [[decision-personal-finance-wallet-voter-reuse]],
  [[decision-personal-finance-system-categories-lazy]],
  [[decision-personal-finance-transfer-legs-guard]] pour les décisions
  d'archi V1 à respecter.
