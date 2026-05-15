---
name: convention-locale-context
description: Convention sur les 3 services locale (LocaleContext, LocaleOptionsProvider, TranslationLocaleSyncer) et la règle d'usage en single_locale_mode
metadata:
  type: feedback
---

## Règle

Pour toute logique qui dépend de la **liste des locales actives**, passer
par les services dans `src/Core/Locale/Service/` plutôt que d'injecter
`kernel.enabled_locales` ou d'utiliser `LocaleEnum::values()` :

- `LocaleContextInterface::getActiveLocales()` — pour itérer dans le code
  applicatif (Managers, ViewBuilders, Serializers, etc.).
- `LocaleContextInterface::getAllLocales()` — pour les **outils statiques**
  indépendants du runtime (ex: `DumpJsTranslationsCommand`, dump
  d'assets) qui doivent traiter toutes les locales du bundle quelque
  soit le mode.
- `LocaleOptionsProviderInterface::getActiveOptions()` — pour produire
  `[{code, label}, ...]` aux composants Vue (onglets/selects). Combine
  `LocaleContext` + `LocaleRepository`.
- `TranslationLocaleSyncerInterface::stale($existing, $inputLocales)` —
  pour calculer quelles `XxxTranslation` supprimer lors d'un `update()`.
  Préserve **toujours** les locales hors mode actif (réversibilité du
  single mode).

## Pourquoi

Le toggle `single_locale_mode` (setting backend, groupe Localization)
est **réversible à chaud** : on ne touche pas au schéma, on filtre
juste à l'écriture/affichage. Cela impose deux invariants :

1. **WRITE = filtered, READ = unfiltered** : les Serializers retournent
   toutes les translations existantes (sinon on perd la trace des
   contenus dormants). Seules les **écritures** (Managers,
   InputFactories) sont filtrées par `getActiveLocales()`.
2. **Cleanup préserve les inactives** : si en single FR mode un admin
   sauve un tag, le `TranslationLocaleSyncer` ne touche pas la row EN
   existante. Sinon rebasculer en multi-langue afficherait des champs
   vides.

Hardcoder `'fr'`/`'en'` ou injecter `kernel.enabled_locales` casse ces
deux invariants et empêche aussi le client de substituer la liste de
locales (`#[AsAlias]` sur les interfaces permet la décoration).

## Comment l'appliquer

- **Backend ViewBuilder** : injecter `LocaleContextInterface` (ou
  `LocaleOptionsProviderInterface` si label DB nécessaire) et passer
  `getActiveLocales()` / `getActiveOptions()` dans la prop `locales` à
  Vue.
- **Manager** qui supprime des translations dans `applyInput()` :
  remplacer le `foreach getTranslations() + removeTranslation` par
  `foreach $this->translationSyncer->stale(...) as $stale` (cf.
  ListingTagManager, ListingCategoryManager, FormManager).
- **Serializer / READ path** : ne **pas** filtrer. Retourner toutes les
  rows existantes.
- **Fallback "translation default"** (ex: `getTranslation('fr')` dans
  un serializer compact) : utiliser
  `$this->localeContext->getDefaultLocale()` au lieu de `'fr'` hardcodé.
  Voir `PostSerializer`, `TaxonomyTermSerializer`, `SearchController`,
  `SitemapBuilder`, `ListingSerializer`.

Lié : [[convention-extensibility]], [[convention-manager-hooks]].

Doc dev : `docs/aurora-core/dev/single_locale_mode.md`.
