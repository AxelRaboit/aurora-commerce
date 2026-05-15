# Mode mono-langue (`single_locale_mode`)

## Vue d'ensemble

Aurora supporte deux modes de fonctionnement linguistique :

- **Multi-langue (défaut)** : toutes les locales déclarées dans
  `LocaleEnum` (actuellement `fr`, `en`) sont actives. Le backend affiche
  un onglet par langue dans les formulaires multilingues (Posts,
  Taxonomies, Forms, ListingTags, ListingCategories, MenuItems), le front
  public expose un `LocaleSwitcher`, et les routes frontend portent
  `/{locale}/...`.
- **Mono-langue** : un toggle dans `/backend/settings` (groupe
  Localization, paramètre `single_locale_mode`) force l'application à
  n'utiliser que la **locale par défaut** (`default_locale`).

Le mode mono-langue est **réversible à chaud** : aucune migration, aucune
suppression de données. Les `XxxTranslation` saisis dans une autre langue
restent en base et **réapparaissent dès qu'on rebascule en multi-langue**.

## Effets quand le mode est ON

1. **Routes frontend** : toute URL `/<autre-locale>/...` est redirigée en
   `301` vers `/<default-locale>/...` par
   `SingleLocaleRedirectSubscriber`. La route avec préfixe reste, mais
   une seule locale est canonique.
2. **Session** : `LocaleSubscriber` ignore la locale stockée en session et
   force `defaultLocale` à chaque requête.
3. **Backend forms** : les ViewBuilders passent
   `LocaleContext::getActiveLocales()` (qui retourne `[defaultLocale]` en
   single mode) à Vue. Les boucles `v-for="locale in locales"` génèrent
   un seul onglet → l'UI multi-langue disparaît visuellement.
4. **Sauvegarde des entités traduisibles** : les Managers utilisent
   `TranslationLocaleSyncer` qui **préserve** systématiquement les
   translations dont la locale n'est pas active (réversibilité). Seules
   les translations actives absentes de l'input sont supprimées.
5. **LocaleSwitcher public** : `Context::activeLocales()` filtre à
   `[defaultLocale]` → le switcher reçoit une liste vide et se cache.
6. **Sitemap / RSS** : les services SEO itèrent sur `Context::activeLocales()`
   et n'émettent donc des entrées que pour la locale par défaut.

## Architecture — les 3 services centraux

Le mode est piloté par 3 services dans `src/Core/Locale/Service/` qui
suivent le pattern Sylius (interface + classe non-`final` + `#[AsAlias]`
pour permettre la substitution côté aurora-client).

### `LocaleContextInterface`

Source de vérité pour l'état des locales à un instant t. Mémoization
in-request (les 2 reads `SettingRepository` ne sont faits qu'une seule
fois par requête).

```php
interface LocaleContextInterface
{
    public function isSingleLocaleMode(): bool;
    public function getDefaultLocale(): string;

    /** @return list<string> Les locales actives (1 seule si single mode). */
    public function getActiveLocales(): array;

    /** @return list<string> Toutes les locales déclarées par le bundle. */
    public function getAllLocales(): array;
}
```

**Quand utiliser quoi** :
- `getActiveLocales()` : pour itérer dans le code applicatif (Managers,
  ViewBuilders, Serializers, formulaires Vue).
- `getAllLocales()` : pour les **outils statiques** indépendants du
  runtime (ex: `DumpJsTranslationsCommand` qui doit produire tous les
  fichiers `assets/locales/generated/{locale}.json` quel que soit le mode).

### `LocaleOptionsProviderInterface`

Produit la liste `[{code, label}, ...]` exploitable par les composants
Vue (onglets/selects), filtrée sur les locales actives. Combine
`LocaleContext` + `LocaleRepository`.

```php
interface LocaleOptionsProviderInterface
{
    /** @return list<array{code: string, label: string}> */
    public function getActiveOptions(): array;
}
```

À injecter dans les ViewBuilders qui ont besoin du label (ex:
`ListingTagsViewBuilder`, `ListingCategoriesViewBuilder`).

### `TranslationLocaleSyncerInterface`

Encapsule la logique de cleanup des `XxxTranslation` en DB lors d'un
`update()`. Pivot du single-locale mode car c'est ici que la
**réversibilité** est garantie : on ne supprime jamais les rows hors
locales actives.

```php
interface TranslationLocaleSyncerInterface
{
    /**
     * @template T
     * @param iterable<string, T> $existing       translations en DB indexées par locale
     * @param list<string>        $inputLocales   locales présentes dans l'input
     * @return list<T>                            translations à supprimer
     */
    public function stale(iterable $existing, array $inputLocales): array;
}
```

**Règle** : tout Manager qui supprime des `XxxTranslation` lors d'un
`applyInput()` doit passer par ce syncer. Sinon, basculer en single FR
détruirait les contenus EN.

## Cheatsheet — étendre une entité multilingue

Quand tu crées une nouvelle entité avec `XxxTranslation` qui doit
respecter le mode mono-langue :

1. **ViewBuilder backend** : injecte `LocaleContextInterface` et passe
   `'locales' => $this->localeContext->getActiveLocales()` à Vue. (Ou
   `LocaleOptionsProviderInterface::getActiveOptions()` si tu as besoin
   du label.)
2. **Manager** :
   - Pour itérer sur les locales à hydrater : utilise les clés de
     `$input->getTranslations()` (le Vue layer envoie déjà la bonne
     liste).
   - Pour supprimer les translations obsolètes : utilise
     `TranslationLocaleSyncerInterface::stale()` au lieu d'un loop
     manuel `foreach + removeTranslation`.
3. **Serializer** : ne pas filtrer les translations en lecture. La règle
   est : **WRITE = filtered, READ = tout**. Cela garantit que les
   translations dormantes (locales inactives) restent visibles dès
   qu'on rebascule.
4. **Tests** : si tu teste un Manager qui dépend de
   `TranslationLocaleSyncer`, instancie un vrai syncer avec un
   `LocaleContext` mocké renvoyant `LocaleEnum::values()` pour garder le
   comportement multi-langue dans les tests.

## Routes frontend en single mode

Les `#[Route('/{locale}/...')]` restent déclarées. C'est
`SingleLocaleRedirectSubscriber` qui canonicalise (`/en/shop` → 301
`/fr/shop`). **Ne pas** retirer le segment `/{locale}` des routes — ça
garderait l'extensibilité multi-langue et permettrait à un projet client
de désactiver le single mode sans modifier les URLs.

## Pièges connus

- **Cache de settings** : `SettingRepository::warmUp()` lit toute la
  table en une fois et mémoize. La memoization in-request de
  `LocaleContext` est une optimisation cosmétique par-dessus, pas une
  nécessité de performance.
- **Subscribers** : `LocaleSubscriber` (priorité 20) et
  `SingleLocaleRedirectSubscriber` (priorité 18) tournent avant le
  RouterListener Symfony. Si tu ajoutes un autre subscriber qui touche
  la locale, attention à l'ordre.
- **`DumpJsTranslationsCommand`** : doit utiliser `getAllLocales()`, pas
  `getActiveLocales()`, car les assets `.json` sont générés à la build
  (indépendamment du runtime).
- **`AbstractOrder::$locale = 'fr'`** et autres `string $locale = 'fr'`
  en params PHP : PHP n'accepte pas une expression non-constante comme
  default value. `LocaleEnum::default()->value` ne fonctionne pas dans
  une signature. Si tu veux retirer le hardcode, passe à `?string
  $locale = null` et résous via `LocaleContext` dans le corps.
