---
name: pattern-locale-aware-extension
description: Comment étendre/respecter la gestion multi-langue (single_locale_mode) côté aurora-client
metadata:
  type: reference
---

## Quand ça te concerne

Trois scénarios distincts. Identifie le tien avant de coder :

### 1. Tu étends une entité multilingue existante d'aurora-core

(Post, Taxonomy, TaxonomyTerm, ListingTag, ListingCategory, Form,
FormField, MenuItem…)

**Tu n'as rien à faire de spécial.** Le Manager parent
(`ListingTagManager` etc.) gère déjà :
- l'itération sur `$input->getTranslations()` reçues du Vue layer
- la suppression sélective via `TranslationLocaleSyncer::stale()` (préserve
  les locales dormantes quand `single_locale_mode` est ON)

Suis le pattern `pattern_extend_entity` + `pattern_extend_manager` + ...
comme d'habitude. La logique locale est transparente.

**Piège** : si tu surcharges `applyInput()` côté client, **appelle
toujours `parent::applyInput($entity, $input)` en premier** (cf.
[[pitfall-call-parent-apply-input]]). Sinon tu court-circuites le syncer
de translations et tu casses la réversibilité du single mode.

### 2. Tu crées une nouvelle entité multilingue côté client

Tu introduis ton propre `<Name>` + `<Name>Translation`. Pour respecter
le single-locale mode :

```php
// Manager client
use Aurora\Core\Locale\Service\TranslationLocaleSyncerInterface;

class CustomTagManager implements CustomTagManagerInterface
{
    public function __construct(
        // ...
        protected readonly TranslationLocaleSyncerInterface $translationSyncer,
    ) {}

    protected function applyInput(CustomTagInterface $tag, CustomTagInputInterface $input): void
    {
        // ... champs scalaires ...

        // Cleanup des translations obsolètes (préserve les locales inactives)
        foreach ($this->translationSyncer->stale($tag->getTranslations(), array_keys($input->getTranslations())) as $stale) {
            $tag->removeTranslation($stale);
        }

        // Upsert des translations actives
        foreach ($input->getTranslations() as $locale => $translationInput) {
            // ...
        }
    }
}
```

Côté ViewBuilder, passer `$this->localeContext->getActiveLocales()` (ou
`$this->localeOptionsProvider->getActiveOptions()` si tu veux le label DB)
à la prop Vue `locales`. **Ne jamais injecter `kernel.enabled_locales`**.

Côté Serializer : **READ = unfiltered**. Retourne toutes les
translations existantes en DB (sinon les locales dormantes deviennent
invisibles et tu casses la réversibilité).

Doc canonique : `docs/aurora-core/dev/single_locale_mode.md` dans
aurora-core.

### 3. Tu veux substituer le comportement de `LocaleContext` lui-même

Use case typique : déterminer la locale via subdomain (`en.example.com`)
au lieu de la session, ou ajouter un canal "locale du contrat client" qui
override la default locale pour certains visiteurs.

Pattern Sylius standard :

```php
use Aurora\Core\Locale\Service\LocaleContext;
use Aurora\Core\Locale\Service\LocaleContextInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: LocaleContextInterface::class)]
class CustomLocaleContext extends LocaleContext
{
    public function getDefaultLocale(): string
    {
        // ta logique custom (subdomain, contrat, etc.)
        // fallback raisonnable :
        return parent::getDefaultLocale();
    }
}
```

Tous les consumers d'aurora-core (Managers, ViewBuilders, Serializers,
`LocaleSubscriber`, `SingleLocaleRedirectSubscriber`) type-hint
`LocaleContextInterface` → ils reçoivent automatiquement ta version.

## Limitation : ajouter une 3e/4e locale

`LocaleEnum` (`Aurora\Core\Locale\Enum\LocaleEnum`) est en dur dans
aurora-core (`French = 'fr'`, `English = 'en'`). Tu ne peux **pas**
ajouter une nouvelle locale (es, de…) depuis aurora-client — il faut
modifier l'enum côté core.

Si tu as besoin d'une locale supplémentaire pour ton projet :
1. Ouvre une issue / PR sur aurora-core pour ajouter le cas à `LocaleEnum`
2. Ajoute les fichiers `messages.<code>.yaml` dans chaque dossier
   `translations/` des modules concernés
3. Le reste (routes, switcher, sitemap, etc.) suit automatiquement via
   `LocaleEnum::values()`.

## Tests

Si ton Manager client dépend de `TranslationLocaleSyncerInterface`,
instancie un vrai syncer avec un `LocaleContextInterface` mocké qui
retourne `LocaleEnum::values()` (= mode multi-langue) :

```php
$localeContext = $this->createMock(LocaleContextInterface::class);
$localeContext->method('getActiveLocales')->willReturn(LocaleEnum::values());
$syncer = new TranslationLocaleSyncer($localeContext);
```

Pour tester le comportement single mode : `willReturn([LocaleEnum::default()->value])`.
