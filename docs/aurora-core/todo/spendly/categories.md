# PersonalFinance — Catégories

## Contexte

Taxonomie des transactions, **scope wallet** (pas global user). Chaque
wallet a son propre jeu de catégories — c'est délibéré côté Spendly pour
éviter qu'une catégorie "Courses" du wallet "Compte courant" se mélange
avec "Courses" du wallet partagé "Vacances".

Deux types de catégories :
- **User** : créées par l'utilisateur via UI
- **System** : générées automatiquement par le code (transferts,
  ajustement de balance) — invisibles dans les listes UI standard

Source Spendly :
- `app/Models/Category.php`
- `app/Enums/SystemCategoryKey.php`
- `app/Services/WalletTransferService.php` (pour la création des
  catégories système)
- `app/Http/Controllers/CategoryController.php`
- `resources/js/Pages/Categories/Index.vue`

## Entité `PersonalFinanceCategory`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint, seq `seq_core_personal_finance_category_id` | — |
| `user_id` | FK `core_user.id` | propriétaire |
| `wallet_id` | FK `core_personal_finance_wallet.id` | cascade ; **scope wallet** |
| `name` | string(120) | — |
| `isSystem` | bool default false | true = créée par code, masquée des listes UI standard |
| `systemKey` | string(120) nullable | unique par wallet, ex : `transfer_income`, `transfer_expense_42`, `balance_adjustment` |
| `createdAt`, `updatedAt` | timestamps | — |

Index :
- `(wallet_id, name)` unique partial WHERE `is_system=false` — empêche
  les user-categories doublons par wallet
- `(wallet_id, system_key)` unique partial WHERE `system_key IS NOT NULL`
  — empêche les system-categories doublons par wallet

> **Pas de hiérarchie parent/enfant en V1.** Spendly n'en a pas ; pas la
> peine d'ajouter de la complexité. Si demande client : ajouter `parent_id`
> nullable + cascade detach plus tard.

## Enum `PersonalFinanceSystemCategoryKey`

```php
enum PersonalFinanceSystemCategoryKey
{
    case TransferIncome;
    case BalanceAdjustment;

    /** Génère 'transfer_expense_{toWalletId}' */
    public static function transferExpenseKey(int $toWalletId): string
    {
        return 'transfer_expense_'.$toWalletId;
    }

    public static function isTransferExpenseKey(string $key): bool
    {
        return str_starts_with($key, 'transfer_expense_');
    }

    public function systemKey(): string
    {
        return match ($this) {
            self::TransferIncome     => 'transfer_income',
            self::BalanceAdjustment  => 'balance_adjustment',
        };
    }
}
```

Le cas `transfer_expense_{id}` est dynamique (un par wallet cible) donc
hors enum. Helper static dans l'enum.

## DTO + Manager + Serializer

Convention 5-couches standard.

### `PersonalFinanceCategoryManager` — particularités

- `applyInput()` standard pour user-categories
- Méthode spécialisée `getOrCreateSystem(User, PersonalFinanceWallet, PersonalFinanceSystemCategoryKey|string $systemKey, string $defaultName): PersonalFinanceCategory`
  — utilisée par `PersonalFinanceTransferService` et `PersonalFinanceBalanceAdjustmentService`
  pour matérialiser une catégorie système lazy
- Hook `protected createCategory()` standard pour substitution
- Validation : refuse `delete()` si `isSystem=true` (cohérence
  applicative)

### Repository particularité

`PersonalFinanceCategoryRepository::findUserCategoriesByWallet(PersonalFinanceWallet $wallet)`
— filtre `is_system=false` par défaut. Méthode séparée
`findSystemByKey(PersonalFinanceWallet $wallet, string $systemKey)` pour le code
métier.

## Suppression

Soft-delete ? **Non.** Hard delete avec `ON DELETE SET NULL` sur les
transactions et les budget items. Préserve l'historique des transactions
(elles deviennent juste "non catégorisées").

Côté UI, prévenir l'utilisateur du nombre de transactions et budget
items qui vont devenir orphelins avant confirmation.

## Vue

`src/Module/PersonalFinance/assets/backend/category/CategoriesApp.vue` :
- Filtres : dropdown wallet, search par nom
- Grille de cards (nom, wallet, usage count via subselect)
- Modale create/edit (`name` + `wallet`)
- Action quick-create depuis transaction form : POST `/backend/personal-finance/categories/quick`
  qui retourne la catégorie pour pré-sélection immédiate

## Extensibilité

- Override `PersonalFinanceCategoryManager::createCategory()` pour substitution
  de classe
- Override `applyInput()` pour des champs custom (ex : icône, couleur,
  parent)
- Slot Vue `extra-form-fields`
- Hook `protected isProtectedFromDeletion(PersonalFinanceCategory): bool` pour
  permettre au client d'ajouter des règles de non-suppression custom

## Pointeurs

- Spendly : `Category.php`, `CategoryController.php`, `SystemCategoryKey.php`
- Aurora : pattern user+system entity → `Aurora\Core\Tag\Entity\Tag` (system tags vs user tags)
