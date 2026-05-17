# PersonalFinance — Auto-catégorisation (Categorization Rules)

## Contexte

Apprentissage de patterns simples description → catégorie. À chaque fois
que l'utilisateur catégorise manuellement une transaction avec une
description, le système crée/incrémente une `PersonalFinanceCategorizationRule`.
Quand il saisit une nouvelle transaction avec la même description (ou
qu'il importe en masse), le système suggère automatiquement la catégorie.

**Pas du machine learning** : juste une lookup deterministe sur
description normalisée (lowercase + sans accents).

Source Spendly :
- `app/Models/CategorizationRule.php`
- `app/Services/CategorizationRuleService.php`
- `app/Http/Controllers/CategorizationRuleController.php`
- `app/Support/Text.php` (normalisation)
- `resources/js/Pages/CategorizationRules/Index.vue`

## Entité `PersonalFinanceCategorizationRule`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint, seq `seq_core_personal_finance_categorization_rule_id` | — |
| `user_id` | FK `core_user.id` | scope user |
| `category_id` | FK `core_personal_finance_category.id` | cascade delete (si catégorie supprimée → règle aussi) |
| `pattern` | string(255) | description normalisée, indexée |
| `hits` | int default 0 | compteur pour ranking |
| `createdAt`, `updatedAt` | timestamps | — |

Index unique sur `(user_id, pattern)` — une seule règle par pattern par
user (l'apprentissage met à jour la catégorie si déjà présent).

> **Scope user, pas wallet.** Une fois apprise, la règle s'applique à
> tous les wallets accessibles (Spendly le fait comme ça, plus
> pragmatique). Si plus tard un client demande "rules par wallet" :
> ajouter `wallet_id` nullable + filtrer.

## Services

Deux services distincts :

### `PersonalFinanceCategorizationLearnService`

```php
class PersonalFinanceCategorizationLearnService
{
    public function learn(User $user, string $description, PersonalFinanceCategory $category): void
    {
        $pattern = self::normalize($description);
        if ($pattern === '') return;

        $rule = $this->ruleRepository->findOneBy(['user' => $user, 'pattern' => $pattern]);
        if ($rule) {
            $rule->setCategory($category);   // peut changer si user re-catégorise
            $rule->incrementHits();
        } else {
            $rule = $this->ruleManager->create($user, $pattern, $category);
        }
        $this->em->flush();
    }

    public static function normalize(string $description): string
    {
        // lowercase + iconv //TRANSLIT pour retirer accents + trim + collapse spaces
        return preg_replace('/\s+/', ' ', trim(mb_strtolower(
            iconv('UTF-8', 'ASCII//TRANSLIT', $description)
        )));
    }
}
```

Appelée depuis `PersonalFinanceTransactionManager::afterSave()` quand la
transaction a une description **et** une catégorie.

### `PersonalFinanceCategorizationSuggestService`

```php
class PersonalFinanceCategorizationSuggestService
{
    public function suggest(User $user, string $description): ?PersonalFinanceCategory
    {
        $pattern = PersonalFinanceCategorizationLearnService::normalize($description);
        return $this->ruleRepository->findOneBy(['user' => $user, 'pattern' => $pattern])?->getCategory();
    }

    /** @return array<string, PersonalFinanceCategory> pattern → catégorie */
    public function suggestBulk(User $user, array $descriptions): array
    {
        $patterns = array_unique(array_map(
            [PersonalFinanceCategorizationLearnService::class, 'normalize'],
            $descriptions
        ));
        $rules = $this->ruleRepository->findBy(['user' => $user, 'pattern' => $patterns]);
        $result = [];
        foreach ($rules as $rule) {
            $result[$rule->getPattern()] = $rule->getCategory();
        }
        return $result;
    }
}
```

Appelé :
- `suggest()` : depuis la modale de création de transaction, à chaque
  fois que l'utilisateur tape une description (debounced) → endpoint
  `GET /backend/personal-finance/categorization-rules/suggest?description=...`
- `suggestBulk()` : par le service `PersonalFinanceImportService` lors du
  preview d'un import Excel pour pré-remplir les catégories

## DTO + Manager + Serializer (CRUD règles)

Pour la page d'administration des règles seulement. Les règles sont
**apprises automatiquement** — l'utilisateur ne les crée jamais à la
main. Il peut juste :
- Changer la catégorie d'une règle (s'il a fait une erreur)
- Supprimer une règle

DTO `PersonalFinanceCategorizationRuleUpdateInput(category)` — uniquement pour
changer la catégorie.

Pas de `PersonalFinanceCategorizationRuleCreateInput` (création toujours via
Learn service).

Manager simple :
- `updateCategory(PersonalFinanceCategorizationRule, PersonalFinanceCategory): void`
- `delete(PersonalFinanceCategorizationRule): void`

## Vue

`assets/Module/PersonalFinance/backend/categorization/CategorizationRulesApp.vue` :
- Tableau paginé, colonnes : `pattern`, `category` (dropdown éditable
  inline), `hits` (sortable), action delete
- Tri par défaut : `hits desc` (règles les plus utilisées en haut)
- Pas de bouton "create" (les règles s'apprennent toutes seules)

Endpoint listing : `GET /backend/personal-finance/categorization-rules?page=1`

## Extensibilité

- Override `PersonalFinanceCategorizationLearnService::normalize()` pour changer
  l'algorithme (ex : stemming, ignorer certains mots)
- Override `PersonalFinanceCategorizationSuggestService::suggest()` pour ajouter
  un layer custom (ex : si pas de hit exact, faire un LIKE fuzzy)
- Hook `protected createRule()` standard pour substitution

> Si demande client : ajouter un mode "strict pattern matching" vs
> "fuzzy" via setting. Pour V1 : pas de fuzzy (cohérent avec Spendly).

## Pointeurs

- Spendly : `CategorizationRule.php`, `CategorizationRuleService.php`,
  `app/Support/Text.php` (normalisation à reproduire)
- Aurora : pattern auto-learning similaire dans
  `Aurora\Module\Crm\Service\ContactDeduplicationLearner` (si existant ;
  sinon, c'est nous qui établissons le pattern)
