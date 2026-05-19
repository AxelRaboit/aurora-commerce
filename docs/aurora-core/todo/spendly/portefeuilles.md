# PersonalFinance — Portefeuilles (Wallets)

> Sous-module **fondateur** du module. À implémenter en premier — tout le
> reste référence `PersonalFinanceWallet`.

## Contexte

Un wallet = un compte/poche financière de l'utilisateur (compte courant,
livret, cash, argent de poche…). Possède un **mode** qui détermine son
comportement et son UI :

- **Budget** : suivi détaillé mensuel via `PersonalFinanceBudget` + `PersonalFinanceBudgetItem` (sections, catégories, carry-over, planification)
- **Simple** : ledger transactions seulement, sans catégories ni budget (idéal pour argent de poche)

Un wallet peut être **partagé** avec d'autres users via `PersonalFinanceWalletMember`
(Owner / Editor / Viewer) — invitations par email/token.

Source Spendly :
- `app/Models/{Wallet,WalletMember,WalletInvitation}.php`
- `app/Enums/{WalletMode,WalletRole}.php`
- `app/Services/{WalletService,WalletMemberService,WalletTransferService}.php` (transfer voir `transactions.md`)
- `app/Http/Controllers/{WalletController,SimpleWalletController,WalletMemberController,WalletInvitationController,BalanceAdjustmentController}.php`
- Migrations : `create_wallets_table.php`, `create_wallet_members_table.php`, `create_wallet_invitations_table.php`
- `resources/js/Pages/Wallets/{Index,Form,Simple/Show}.vue`

## Entités à créer

### 1. `PersonalFinanceWallet`

```php
namespace Aurora\Core\Module\PersonalFinance\Wallet\Entity;

// PersonalFinanceWalletInterface + AbstractPersonalFinanceWallet + concrete PersonalFinanceWallet
```

Champs :
| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint, seq `seq_core_personal_finance_wallet_id` | — |
| `owner_id` | FK `core_user.id` | créateur original = Owner par défaut |
| `name` | string(120) | nom affiché |
| `startBalance` | decimal(10,2) default 0 | balance initiale à la date de création |
| `mode` | enum `PersonalFinanceWalletMode` (`budget`/`simple`) | gated comportement |
| `showOnDashboard` | bool default true | "pinned" |
| `position` | int default 0 | tri drag-drop |
| `createdAt`, `updatedAt` | timestamps | trait standard Aurora |

> Pas de `is_demo` (cf. README §"exclu").
> Balance courante = `startBalance + SUM(income) - SUM(expense)`, **calculée à la volée**, pas stockée.

Méthodes domaine (sur l'entité) :
- `isBudgetMode(): bool` / `isSimpleMode(): bool`
- `roleFor(User $user): ?PersonalFinanceWalletRole` (parcourt members)
- `isShared(): bool` (members.count() > 1)

### 2. `PersonalFinanceWalletMember`

```php
namespace Aurora\Core\Module\PersonalFinance\Wallet\Entity;
```

Pivot user ↔ wallet avec rôle. Unique sur `(wallet_id, user_id)`.

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint, seq `seq_core_personal_finance_wallet_member_id` | — |
| `wallet_id` | FK `core_personal_finance_wallet.id` | cascade delete |
| `user_id` | FK `core_user.id` | restrict delete |
| `role` | enum `PersonalFinanceWalletRole` | Owner / Editor / Viewer |
| `createdAt` | timestamp | — |

### 3. `PersonalFinanceWalletInvitation`

```php
namespace Aurora\Core\Module\PersonalFinance\Wallet\Entity;
```

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint, seq `seq_core_personal_finance_wallet_invitation_id` | — |
| `wallet_id` | FK | cascade delete |
| `invitedBy_id` | FK `core_user.id` | — |
| `email` | string(180) | — |
| `role` | enum `PersonalFinanceWalletRole` | **Owner exclu** (transfer ownership explicite via flow séparé) |
| `token` | string(64) unique | URL-safe random_bytes(32) |
| `expiresAt` | datetime | typiquement +14 jours |
| `acceptedAt`, `declinedAt` | datetime nullable | — |
| `createdAt` | timestamp | — |

Unique sur `(wallet_id, email)` pour éviter les doublons.

## Enums

```php
enum PersonalFinanceWalletMode: string {
    case Budget = 'budget';
    case Simple = 'simple';
}

enum PersonalFinanceWalletRole: string {
    case Owner  = 'owner';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function canEdit(): bool { return $this !== self::Viewer; }
    public function canManageMembers(): bool { return $this === self::Owner; }
    public function canDelete(): bool { return $this === self::Owner; }

    /** @return array<self> roles invitable (Owner exclu) */
    public static function invitable(): array { return [self::Editor, self::Viewer]; }
}
```

## DTO + Manager + Serializer

Convention 5-couches stricte. Pour chacune des 3 entités :

- `<Name>InputInterface`, `<Name>Input` (non-final, `public readonly` par prop), `<Name>InputFactoryInterface`, `<Name>InputFactory` avec `#[AsAlias]`
- `<Name>ManagerInterface` dans `Manager/`, `<Name>Manager` non-final, hooks `protected create<X>()` + `applyInput()` + `auditPayload()` + `audit<Created|Updated|Deleted>`
- `<Name>SerializerInterface`, `<Name>Serializer` non-final, `#[AsAlias]`

### `PersonalFinanceWalletManager` — particularités

Crée automatiquement un `PersonalFinanceWalletMember(wallet, owner, Owner)` à
la création du wallet. Hook dédié :
```php
protected function createOwnerMembership(PersonalFinanceWalletInterface $wallet, User $owner): PersonalFinanceWalletMemberInterface
```

Empêche la suppression d'un wallet partagé sans confirmation (à gérer
côté controller via question modale, pas dans le manager).

### `PersonalFinanceWalletMemberManager` — particularités

Méthode dédiée `transferOwnership(PersonalFinanceWalletMember $current, PersonalFinanceWalletMember $new)` :
- Doit être atomique (transaction Doctrine)
- `$new.role = Owner`, `$current.role = Editor`, `$wallet.owner_id = $new.user_id`
- Empêcher si `$new.role === Owner` déjà ou si `$new.wallet !== $current.wallet`

> User-style multi-méthodes : pas de `applyInput()` générique. 5 méthodes
> spécialisées (`updateRole`, `transferOwnership`, `removeMember`, …)
> avec validation distincte par opération. Cf. §3 "variantes Manager"
> dans la convention extensibilité.

### `PersonalFinanceWalletInvitationManager`

- `send(...)` : crée invitation, génère token sécurisé, envoie email
- `accept(string $token, User $accepter)` : valide token (non expiré, non
  utilisé), crée `PersonalFinanceWalletMember`, marque `acceptedAt`
- `decline(string $token)` : marque `declinedAt`
- `resend(PersonalFinanceWalletInvitation)` : régénère token + email (reset
  `expiresAt`)
- `revoke(PersonalFinanceWalletInvitation)` : soft-delete (status set to declined par owner)

## Voter Symfony

`Aurora\Core\Module\PersonalFinance\Wallet\Voter\PersonalFinanceWalletVoter` :

```php
const VIEW              = 'FINANCE_WALLET_VIEW';
const EDIT              = 'FINANCE_WALLET_EDIT';           // update name, mode, etc.
const EDIT_TRANSACTIONS = 'FINANCE_WALLET_EDIT_TX';
const MANAGE_MEMBERS    = 'FINANCE_WALLET_MEMBERS';
const DELETE            = 'FINANCE_WALLET_DELETE';
```

Logique :
- `VIEW` : true si user a un `PersonalFinanceWalletMember` quelconque sur ce wallet
- `EDIT_TRANSACTIONS` : Owner ou Editor
- `EDIT` / `MANAGE_MEMBERS` / `DELETE` : Owner only

> Réutilisable depuis **tous** les autres sous-modules (transaction,
> budget, goal, recurring, etc.) pour vérifier les permissions sur leur
> wallet parent.

## Service annexe : `PersonalFinanceWalletBalanceService`

Pas un Manager (pas de persistance), mais service stateless pour calculs :

```php
public function currentBalance(PersonalFinanceWallet $wallet): string;
public function monthlyBalance(PersonalFinanceWallet $wallet, DateImmutable $month): string;
public function rollingStartBalance(PersonalFinanceWallet $wallet, DateImmutable $month): string;
```

Cf. `BudgetService::computeRollingStartBalance` côté Spendly pour la
formule exacte.

## Adjustement de balance d'ouverture

Reprise du flow `BalanceAdjustmentController` Spendly : permet à
l'utilisateur de "corriger" sa balance courante en créant automatiquement
une transaction d'ajustement (income ou expense selon le delta).

- Endpoint `POST /backend/personal-finance/wallets/{wallet}/balance-adjustment`
- DTO `PersonalFinanceBalanceAdjustmentInput(targetBalance, date, description?)`
- Service `PersonalFinanceBalanceAdjustmentService` :
  1. Calcule `currentBalance`
  2. Calcule `diff = targetBalance - currentBalance`
  3. Crée transaction via `PersonalFinanceTransactionManager` (passe par le manager pour audit + categorization rules)
  4. Type = Income si diff > 0, Expense sinon, montant = abs(diff)
  5. Catégorie système `system_balance_adjustment` créée lazily

## Vue

`src/Module/PersonalFinance/assets/backend/wallet/` :
- `WalletsApp.vue` : grille de cards, drag-drop reorder, modal create/edit, modal members, modal transfer (cf. transactions.md)
- `simple/SimpleWalletApp.vue` : page d'un wallet en mode Simple — liste transactions du mois, filtres type, search, modal create/edit transaction, modal adjustment
- `WalletInvitationRespondApp.vue` : page publique (auth requise pour accept) `/wallet-invitations/{token}` → bouton accept/decline

`usePersonalFinanceWalletForm.js`, `usePersonalFinanceWalletMembers.js` composables.

Le wallet en mode Budget = page `BudgetWalletApp.vue` traitée dans
[`automatiques.md` n'est pas le bon endroit — c'est sous une vue dédiée
`Budget/`, hors scope de cette fiche]. **Voir aussi le sous-module
Budget** : pour V1 ce sera traité dans la fiche `portefeuilles.md` mais
considéré "section budget" — à splitter si la fiche dépasse 600 lignes.

## Extensibilité

- Override `PersonalFinanceWalletManager::createWallet()` pour brancher une logique
  custom (ex : créer wallet companion auto)
- Override `PersonalFinanceWalletInvitationManager::sendInvitationEmail()` pour
  changer le mail (custom template / SMS / webhook)
- Slot Vue `extra-form-fields` sur le formulaire de création (ex : champ
  IBAN, devise spécifique)
- Hook `protected resolveOwnerRole()` pour permettre un autre rôle par
  défaut que Owner (cas multi-tenant strict où "Admin" passe au-dessus)

## Pointeurs

- Spendly : tous les fichiers listés en intro
- Aurora — patterns référence :
  - Voter : `Aurora\Module\Project\Voter\TaskVoter` (ownership + membres)
  - Invitation flow : `Aurora\Module\Platform\User\Manager\UserInvitationManager`
  - Pivot user-ressource : `Aurora\Module\Platform\User\Entity\UserAgencyMembership`
