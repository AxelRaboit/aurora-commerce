# PersonalFinance — Transactions

> Cœur applicatif du module. Tout le reste (budget, goals, stats, import)
> n'a de sens que parce qu'on enregistre des transactions.

## Contexte

Une `PersonalFinanceTransaction` est l'événement financier atomique : dépense ou
revenu, attaché à un wallet, optionnellement à une catégorie, avec une
date, un montant positif et un type (`Income`/`Expense`).

Modélisations spéciales :
- **Virements** : 2 transactions liées par `transferId` (UUID) — pas de
  3e type
- **Splits** : N transactions liées par `splitId` (UUID) — décomposition
  d'une dépense en sous-postes catégorisés
- **Attachments** : 1 fichier (justificatif) par transaction, stocké hors
  document root

Source Spendly :
- `app/Models/Transaction.php`
- `app/Services/{TransactionService,WalletTransferService}.php`
- `app/Http/Controllers/{TransactionController,SimpleWalletController,WalletTransferController}.php`
- `resources/js/Components/transactions/TransactionModal.vue` (et déclinaisons)

## Entité `PersonalFinanceTransaction`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint, seq `seq_core_personal_finance_transaction_id` | — |
| `user_id` | FK `core_user.id` | propriétaire (=  créateur ; pas forcément owner du wallet) |
| `wallet_id` | FK `core_personal_finance_wallet.id` | cascade delete |
| `category_id` | FK `core_personal_finance_category.id` nullable | nullable car wallet Simple peut être sans cat. |
| `type` | enum `PersonalFinanceTransactionType` | Income / Expense |
| `amount` | decimal(10,2) | toujours positif, signe = type |
| `description` | string(255) nullable | normalisé pour categorization rules |
| `date` | date | YYYY-MM-DD |
| `tags` | json nullable | array<string>, libre |
| `transferId` | uuid nullable | groupe 2 tx d'un transfer |
| `splitId` | uuid nullable | groupe N tx d'un split |
| `attachmentPath` | string(255) nullable | path relatif sous `var/uploads/personal-finance/transactions/` |
| `createdAt`, `updatedAt` | timestamps | — |

Index requis :
- `(wallet_id, date)` — listing wallet mensuel
- `(user_id, date)` — agrégats user multi-wallet
- `(category_id, date)` — budget item actuals
- `(transfer_id)` — lookup transfer
- `(split_id)` — lookup split

Enum :
```php
enum PersonalFinanceTransactionType: string {
    case Income  = 'income';
    case Expense = 'expense';
}
```

## DTO + Manager + Serializer

Convention 5-couches stricte.

### `PersonalFinanceTransactionManager` — particularités

```php
class PersonalFinanceTransactionManager implements PersonalFinanceTransactionManagerInterface
{
    protected function createTransaction(): PersonalFinanceTransactionInterface { return new PersonalFinanceTransaction(); }

    protected function applyInput(PersonalFinanceTransactionInterface $t, PersonalFinanceTransactionInputInterface $input): void
    {
        $t->setWallet($input->wallet);
        $t->setCategory($input->category);
        $t->setType($input->type);
        $t->setAmount($input->amount);
        $t->setDescription(self::normalizeDescription($input->description));
        $t->setDate($input->date);
        $t->setTags($input->tags ?? []);
    }

    protected function afterSave(PersonalFinanceTransactionInterface $t, bool $isNew): void
    {
        if ($t->getDescription() && $t->getCategory()) {
            $this->categorizationLearnService->learn($t->getUser(), $t->getDescription(), $t->getCategory());
        }
    }
    // + audit hooks standard
}
```

> `afterSave` est un nouveau hook à standardiser dans la convention. Si
> ça n'existe pas encore globalement, c'est l'occasion de le formaliser
> dans [`entity_extensibility_convention.md`](../../dev/entity_extensibility_convention.md) §3.

## Service `PersonalFinanceTransferService`

Modélise un virement entre 2 wallets. **Service séparé du Manager** car
ce n'est pas un CRUD entité simple — c'est une opération atomique sur
**2 transactions**.

```php
class PersonalFinanceTransferService
{
    public function create(User $user, PersonalFinanceTransferInput $input): string  // returns transferId UUID
    {
        // 1. Crée/récupère catégories système : 'transfer_income' + 'transfer_expense_{toWalletId}'
        // 2. UUID transferId = Uuid::v7()
        // 3. Wraps in transaction Doctrine
        // 4. financeTransactionManager->create(...) pour Expense from-wallet
        // 5. financeTransactionManager->create(...) pour Income to-wallet
        // 6. Les deux partagent transferId
    }

    public function update(string $transferId, User $user, PersonalFinanceTransferInput $input): void;
    public function delete(string $transferId, User $user): void;

    protected function getOrCreateSystemCategory(User $user, PersonalFinanceWallet $wallet, PersonalFinanceSystemCategoryKey $key): PersonalFinanceCategory;
}
```

DTO `PersonalFinanceTransferInput(fromWallet, toWallet, amount, date, description?)`.

Catégories système (cf. `categories.md`) :
- `transfer_income` : créée lazily dans le wallet **receveur**
- `transfer_expense_{toWalletId}` : créée lazily dans le wallet
  **émetteur**, avec key unique par wallet destinataire (pour permettre
  des stats granulaires "virements vers compte X")

## Splits (option Pro côté Spendly — porté **sans gating** dans Aurora)

Décomposition d'une transaction en N sous-postes catégorisés.
Exemple : 1 achat supermarché 100€ → 60€ "Courses", 30€ "Hygiène", 10€
"Animaux".

- DTO `PersonalFinanceTransactionSplitInput(wallet, date, description?, splits: array{category, amount, description?}[])`
- `PersonalFinanceTransactionManager::createSplit()` :
  - Validation : `sum(splits.amount) > 0`
  - UUID splitId = Uuid::v7()
  - Crée N transactions partageant le splitId
- `PersonalFinanceTransactionManager::deleteSplit(splitId)` : supprime toutes les
  transactions du split en cascade

Pas de "parent transaction" : juste un splitId commun. Affichage côté Vue
groupe par splitId quand présent.

## Attachments

1 fichier par transaction (justificatif). Stockage sous
`var/uploads/personal-finance/transactions/{transactionId}/{originalName}` (cf.
[convention storage](../../../.claude/memory/aurora-shared/convention_storage_var_uploads.md)).

Service `PersonalFinanceTransactionAttachmentService` :
- `attach(PersonalFinanceTransaction $t, UploadedFile $file): void`
- `detach(PersonalFinanceTransaction $t): void` (efface le fichier sur disque)
- `serve(PersonalFinanceTransaction $t): BinaryFileResponse` (avec ACL voter VIEW
  sur le wallet)

Route gating : `/backend/personal-finance/transactions/{transaction}/attachment`
(pas le catch-all `/uploads/{path}` — on veut l'ACL voter).

Cleanup orphans : sur `deleteTransaction`, supprimer le dossier. Sur
`detach`, supprimer le fichier.

## Vue (résumé — détaillé page par page dans autres fiches)

Composant central réutilisé partout : `PersonalFinanceTransactionFormModal.vue`
(props : `wallet`, `transaction?`, `transferMode?`, `splitMode?`).
Émet `@saved` avec la transaction créée/modifiée.

Composables :
- `usePersonalFinanceTransactionForm.js` — gère create/edit unifié + autocompletion catégorie via categorization rules suggest
- `usePersonalFinanceTransfer.js` — pour la modale transfer
- `usePersonalFinanceSplit.js` — pour le mode split

Le "transactions" entry de la sidebar Spendly est en réalité un **search
multi-wallet** (cf. `SearchController` côté Spendly). À porter comme
`src/Module/PersonalFinance/assets/backend/transaction/TransactionsSearchApp.vue` :
- Liste paginée toutes wallets accessibles
- Filtres : wallet, catégorie, type, mois, search description, tags
- Actions inline : edit, delete (modal de confirmation)

## Voter

Pas de voter propre — délègue au `PersonalFinanceWalletVoter` :
- VIEW transaction = VIEW wallet
- EDIT transaction = EDIT_TRANSACTIONS wallet
- DELETE transaction = EDIT_TRANSACTIONS wallet

## Extensibilité

- Override `PersonalFinanceTransactionManager::createTransaction()` pour
  substituer la classe (cas client : `MyPersonalFinanceTransaction extends PersonalFinanceTransaction`
  avec colonnes custom)
- Override `applyInput()` pour mapper des champs custom du DTO étendu
- Override `afterSave()` pour brancher d'autres side-effects (webhook,
  notification…)
- Slots Vue : `extra-form-fields` dans `PersonalFinanceTransactionFormModal.vue`
- Hook DTO `PersonalFinanceTransactionInputFactory::buildExtraFields()` pour
  permettre au client d'extraire ses champs custom de la request

## Edge cases à gérer

- **Suppression d'un wallet partagé** : empêcher si transactions présentes
  (ou bien soft-archive + nudge) — choix produit, par défaut "interdire"
- **Suppression d'une catégorie** : transactions deviennent
  `category_id = null` (ON DELETE SET NULL) — preserve l'historique
- **Suppression d'une transaction membre d'un transfer** : supprimer
  l'autre côté du transfer aussi (manager refuse l'opération seule, faut
  passer par `PersonalFinanceTransferService::delete($transferId)`)
- **Suppression d'une transaction membre d'un split** : idem, refuse →
  `PersonalFinanceTransactionManager::deleteSplit($splitId)`

## Pointeurs

- Spendly : `TransactionService.php`, `WalletTransferService.php`,
  `TransactionController.php`
- Aurora : 
  - Pattern Manager + categorization (similaire à
    `Aurora\Module\Crm\Tag\Service\TagSuggestionService`)
  - Pattern attachment storage : `Aurora\Module\Photo\Service\PhotoStorageService`
