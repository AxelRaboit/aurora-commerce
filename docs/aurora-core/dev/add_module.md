# Créer un nouveau module dans aurora-core

Guide pratique pour scaffolder un module Symfony sous `src/Module/<Module>/`.
Couvre **5 cas types** par ordre de complexité croissante, plus l'infrastructure
auto-découverte commune.

> **Mémoires architecture connexes** (à lire pour les décisions transverses) :
> - [`pattern_core_submodules_split.md`](../../../.claude/memory/aurora-core/architecture/pattern_core_submodules_split.md) — "1 module = 1 section = 1 toggle root = 1 context"
> - [`architecture_module_parameter_enum.md`](../../../.claude/memory/aurora-core/architecture/architecture_module_parameter_enum.md) — toggles cascade graph + convention clés
> - [`pattern_user_scoped_module_access.md`](../../../.claude/memory/aurora-core/architecture/pattern_user_scoped_module_access.md) — `ModuleAccessChecker` global + per-user
> - [`pattern_frontend_descriptor.md`](../../../.claude/memory/aurora-core/architecture/pattern_frontend_descriptor.md) — `<Module>FrontendDescriptor` pattern
> - [`pattern_configuration_tab_provider.md`](../../../.claude/memory/aurora-core/architecture/pattern_configuration_tab_provider.md) — onglets Settings

---

## 1. Quand créer un module ?

Un **module** = un domaine métier cohérent sous `src/Module/<Module>/`. Cinq cas
types se cumulent (un module complexe en assemble plusieurs) :

| Cas | Quand | Exemple canonique |
|---|---|---|
| **1. Stateless minimal** | Pas d'entité, juste 1 controller + 1 UI | `PasswordGenerator` (sous-module de Vault) |
| **2. Sous-features togglables** | Plusieurs features indépendamment activables | `Vault` (Safe + PasswordGenerator) |
| **3. Avec entités CRUD** | Persistance Doctrine + extensibilité Sylius | `Editorial`, `Crm`, `Billing` |
| **4. Avec frontend public** | Pages publiques (pas que back-office) | `Photo`, `Editorial`, `Ecommerce`, `Ged` |
| **5. Avec settings** | Onglet dans la page admin Settings | `Crm`, `Photo`, `Ged`, `Editorial`, … |

Chaque cas se construit sur le précédent. Si tu démarres avec un module simple,
commence par le cas 1 — tu pourras ajouter les autres au fur et à mesure.

---

## 2. Scaffolding rapide avec `aurora:make:module`

**Le chemin recommandé** pour créer un nouveau module. La commande génère
tout le squelette du **Cas 1 + Cas 2 (toggle backend par défaut)**, et
peut empiler les cas 4 et 5 via des flags. Le cas 3 (entités CRUD) reste
manuel — déféré à `/add-entity` ou `bin/console make:entity`.

### 2.1 Utilisation

```bash
# Module minimal togglable (cas 1 + 2 — défaut)
php bin/console aurora:make:module Loyalty

# Module avec frontend public (cas 4)
php bin/console aurora:make:module Loyalty --with-frontend

# Module avec onglet Settings (cas 5)
php bin/console aurora:make:module Loyalty --with-settings

# Tout combiné
php bin/console aurora:make:module Loyalty --with-frontend --with-settings

# Module infra-only (always-on, pas de toggle backend) — Dev-style
php bin/console aurora:make:module DevTools --no-toggle
```

| Flag | Effet |
|---|---|
| _(aucun)_ | Cas 1 + Cas 2 : module togglable depuis l'admin "Modules access" panel |
| `--no-toggle` | Opt-out du toggle backend ; le module est always-on (réservé aux infras type Dev) |
| `--with-frontend` | Cas 4 : ajoute `<X>FrontendDescriptor.php` (pages publiques) |
| `--with-settings` | Cas 5 : ajoute `Setting/<X>SettingEnum.php` + `<X>ConfigurationTabProvider.php` |

La commande **auto-détecte le contexte** (core vs client) en lisant
`composer.json` (`"name": "axelraboit/aurora"` → core, sinon client).

### 2.2 Fichiers générés (défaut, cas 1 + 2)

```
src/Module/<Module>/<Module>Module.php          # implements ModuleInterface + ModuleToggleProviderInterface
src/Module/<Module>/<Module>Context.php          # isBackendEnabled() + BACKEND_KEY (client)
src/Module/<Module>/Controller/Backend/<Module>Controller.php
src/Module/<Module>/templates/backend/index.html.twig
src/Module/<Module>/translations/messages.{fr,en}.yaml
src/Module/<Module>/assets/backend/<Module>App.vue
aliases.js                                       # core only : append @<kebab>
```

Plus, si **client**, trois fichiers de config sont auto-patchés (aurora-core
ne peut pas auto-découvrir les modules client — son glob ne voit que
`vendor/.../src/Module/*`) :

```
config/packages/twig.yaml         # ajout namespace @<Module>
config/packages/framework.yaml    # ajout path translations pour Symfony Twig |trans
config/services.yaml              # ajout path translations pour DumpJsTranslationsCommand (vue-i18n)
```

L'icône du NavItem est **hardcodée à `flame`** (le seul prompt sur l'icône
serait friction inutile — change la string dans `<Module>Module.php` après
scaffold si tu veux autre chose, en piochant dans
`src/Core/Frontend/backend/sidemenu/composables/useSidemenuNav.js` ICON_MAP).

### 2.3 Prompts interactifs

Si tu lances la commande sans `--no-interaction`, deux confirmations :
- **Public-facing pages?** → équivalent à `--with-frontend`
- **Own tab in /backend/settings?** → équivalent à `--with-settings`

Puis trois inputs :
- **Display label** (défaut = nom du module en PascalCase) — texte libre pour la nav
- **NavSection priority** (défaut = 60) — plus bas = plus haut dans le sidemenu

### 2.4 Next steps après scaffold

La commande imprime à la fin un récap de commandes à lancer **sans
quoi le module ne sera pas opérationnel** :

```bash
# Si cas 2 (défaut) côté core : enregistrer le toggle dans ModuleParameterEnum
# (voir doc cas 2 plus bas — étape manuelle qu'on n'automatise pas pour PHPStan)

make sf CMD="aurora:application-parameter"   # core only, sync l'enum case en BDD
make sf CMD="aurora:privileges:sync"         # enregistre la permission <module>.use
make sf CMD="aurora:menus:sync"              # enregistre le NavItem
make translation                             # dump JSON traductions pour vue-i18n
make cc                                      # clear cache (Twig + Symfony)
```

### 2.5 Limites de la commande

- **Refondre un module existant** : la commande refuse si `src/Module/<X>/`
  existe déjà — édition manuelle nécessaire (ou suppression du module avant
  de relancer le scaffold).

### 2.6 Étapes complémentaires après scaffold

Le maker ne couvre **que** Cas 1, 2, 4, 5 — pas le contenu métier. Pour
aller plus loin :

- **Ajouter une entité CRUD** (cas 3) : lancer `/add-entity` (skill Claude
  Code) en ciblant le module fraîchement scaffolddé.
- **Ajouter une sous-feature togglable** à un module existant (Vault.Safe +
  Vault.PasswordGenerator) : lancer `/add-submodule` plutôt que de
  re-scaffolder.

---

## 3. Cas 1 — module stateless minimal

### 3.1 `<Module>Module.php` — inscription

```php
// src/Module/MyModule/MyModuleModule.php
namespace Aurora\Module\MyModule;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Nav\NavPermission;

final readonly class MyModuleModule implements ModuleInterface
{
    public function getId(): string { return 'my_module'; }

    public function getPermissions(): array
    {
        return [new NavPermission('my_module.use')];
    }

    public function getNavSections(): array        { return []; }
    public function getCatalogNavSections(): array { return []; }
}
```

**Règles :**
- Namespace **`Aurora\Module\<Module>`** (pas `Aurora\Core\Module`).
- `ModuleInterface` est dans `Aurora\Core\Module\Contract\` (4 méthodes obligatoires).
- `getId()` = clé snake_case unique (= préfixe permissions, settings, etc.).
- **Auto-tag** : `_instanceof: ModuleInterface: tags: [aurora.module]` dans
  `config/services.yaml` enregistre le module sans wiring manuel.
- Si le module rejoint une section nav existante (ex. PasswordGenerator → `vault`) :
  laisser `getNavSections()` vide et ajouter le `NavItem` dans le module
  propriétaire (cf. cas 2). Sinon, déclarer ici la `NavSection`.
- `getCatalogNavSections()` retourne les nav items **même si le module est
  désactivé** (pour l'affichage du catalogue/picker dans la page Users).

### 3.2 Controller

```php
// src/Module/MyModule/Controller/Backend/MyModuleController.php
namespace Aurora\Module\MyModule\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/my-module', name: 'backend_my_module')]
#[IsGranted('my_module.use')]
final class MyModuleController extends AbstractController
{
    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@MyModule/backend/index.html.twig');
    }
}
```

**Règles :**
- `final class` (pas `final readonly`) — les controllers Symfony ne peuvent pas
  être `readonly` car `setContainer()` est appelé après instanciation.
- Permission string = `<module_id>.<action>` (`my_module.use`, `vault.password_generator.use`).
- Route prefix kebab-case = `/backend/<module-id-en-kebab>`.

### 3.3 Template Twig

```twig
{# src/Module/MyModule/templates/backend/index.html.twig #}
{% extends '@Core/backend/layout.html.twig' %}

{% block title %}{{ 'backend.nav.my_module'|trans }} - {{ parent() }}{% endblock %}

{% block page_header_slot %}
    {{ include('@Shared/components/page_header.html.twig', {
        crumbs: [
            {label: 'backend.nav.sections.my_section'|trans},
            {label: 'backend.nav.my_module'|trans},
        ],
    }) }}
{% endblock %}

{% block body %}
<div {{ vue_component('mymodule/backend/MyModuleApp', {}) }} class="flex-1 min-w-0"></div>
{% endblock %}
```

- Le namespace `@MyModule` est auto-monté par `AuroraBundle` à partir de
  `src/Module/<Module>/templates/` — templates co-localisés avec le code PHP
  du module (parallèle à `assets/` et `translations/`). Vérifié dans
  `AuroraBundle.php` (boucle sur `$moduleDirs`).
- `vue_component('<module_id_lowercase>/<path>', props)` : le helper Twig
  résout vers le composant Vue chargé via le glob `src/Module/*/assets/**/*.vue`
  (`src/Core/Frontend/app.js:33+57-65` — lowercase conversion appliquée au nom du module).

### 3.4 Traductions

```yaml
# src/Module/MyModule/translations/messages.fr.yaml
backend:
  modules:
    my_module: Mon module
  nav:
    my_module: Mon module
    my_module_description: Description courte pour les tooltips

my_module:
  title: Mon module
  # … clés UI spécifiques au composant Vue
```

> **Note** : les permissions (`backend.permissions.names.my_module.use`) ne sont
> nécessaires que si tu exposes l'admin Users/Permissions au libellé custom.
> Pour les sous-modules d'un module parent (ex. PasswordGenerator → Vault), les
> traductions de permission vivent dans le **module parent** qui déclare les
> `NavPermission`.

### 3.5 Composant Vue + alias

**Alias dans `aliases.js`** (source unique pour Vite + Vitest) :

```js
// aliases.js
"@my-module": moduleAlias("MyModule"),
```

**Composant principal :**

```vue
<!-- src/Module/MyModule/assets/backend/MyModuleApp.vue -->
<script setup>
// Composables : choisir le bon endroit
//  - logique réutilisable cross-modules → @shared/composables/
//  - logique propre au module → @my-module/backend/composables/
import { useMyFeature } from '@my-module/backend/composables/useMyFeature.js';
const { /* ... */ } = useMyFeature();
</script>
```

> **Précédent** : `PasswordGeneratorApp.vue` importe son composable depuis
> `@shared/composables/usePasswordGenerator.js` parce qu'il est réutilisable.
> Quand c'est le cas, **pas besoin de créer un dossier `composables/` vide**
> dans le module.

---

## 4. Auto-découverte — zéro wiring manuel

Vérifié dans `AuroraBundle.php` (Symfony 7) et `config/services.yaml`.

| Chose | Mécanisme | Source |
|---|---|---|
| Service Symfony | `Aurora\: resource: '../src/'` | `config/services.yaml:27` |
| Tag `aurora.module` | `_instanceof: Aurora\Core\Module\Contract\ModuleInterface: tags: [aurora.module]` | `config/services.yaml:30-36` |
| Namespace Twig `@MyModule` | glob `src/Module/*` + `src/Module/MyModule/templates/` | `AuroraBundle.php` (`prependExtension`) |
| Paths traductions | glob `src/Module/*/translations/` | `AuroraBundle.php` (`prependExtension`, bloc `framework.translator.paths`) |
| `DumpJsTranslationsCommand` | même glob | idem |
| Composants Vue | glob `import.meta.glob('./Module/**/*.vue')` + lowercase | `src/Core/Frontend/app.js:33,57-65` |

**Seul wiring manuel restant** : `resolve_target_entities` dans
`AuroraBundle.php` — uniquement si le module a des entités Doctrine
(cf. cas 3).

---

## 5. Cas 2 — module avec sous-features togglables

Quand un module a plusieurs sous-features indépendamment activables (Vault =
Safe + PasswordGenerator, Editorial = blog + pages, …), suivre le pattern
**`ModuleToggleProviderInterface` + Context class** :

### 5.1 `<Module>Context` — orchestration des feature-flags

Centralise les checks de toggles dans une classe dédiée injectée dans le module
et tous les services concernés. Permet de garder les `if (! $context->isXEnabled())`
**hors** du `<Module>Module.php`.

```php
// src/Module/Vault/VaultContext.php
namespace Aurora\Module\Vault;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class VaultContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::VaultBackend);
    }

    public function isSafeEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::VaultSafe);
    }

    public function isPasswordGeneratorEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::VaultPasswordGenerator);
    }
}
```

### 5.2 `ModuleParameterEnum` — déclarer les toggles

Ajouter les cases dans `src/Core/Setting/Enum/ModuleParameterEnum.php` :
- Convention clés : `<module>_<feature>` (pas `_enabled` à la fin —
  cf. [`architecture_module_parameter_enum.md`](../../../.claude/memory/aurora-core/architecture/architecture_module_parameter_enum.md))
- Définir le parent dans le cascade graph (ex. `VaultSafe.parent = VaultBackend`)

### 5.3 `<Module>Module` avec `ModuleToggleProviderInterface`

```php
// src/Module/Vault/VaultModule.php (extrait)
final readonly class VaultModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private VaultContext $vaultContext) {}

    public function getId(): string { return 'vault'; }

    public function getPermissions(): array
    {
        return [
            new NavPermission('vault.use'),
            new NavPermission('vault.password_generator.use'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->vaultContext->isBackendEnabled()) { return []; }

        $items = [];
        if ($this->vaultContext->isSafeEnabled()) {
            $items[] = new NavItem('backend_vault', 'backend.nav.vault', 'vault',
                requiredPrivilege: 'vault.use',
                descriptionKey: 'backend.nav.vault_description');
        }
        if ($this->vaultContext->isPasswordGeneratorEnabled()) {
            $items[] = new NavItem('backend_password_generator',
                'backend.nav.password_generator', 'key-round',
                requiredPrivilege: 'vault.password_generator.use',
                descriptionKey: 'backend.nav.password_generator_description');
        }

        return [] === $items ? [] : [new NavSection('vault', $items, priority: 20)];
    }

    /** Retourne TOUS les items même si désactivés — pour le picker catalogue. */
    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('vault', [
                new NavItem('backend_vault', 'backend.nav.vault', 'vault',
                    requiredPrivilege: 'vault.use',
                    descriptionKey: 'backend.nav.vault_description'),
                new NavItem('backend_password_generator',
                    'backend.nav.password_generator', 'key-round',
                    requiredPrivilege: 'vault.password_generator.use',
                    descriptionKey: 'backend.nav.password_generator_description'),
            ], priority: 20),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::VaultBackend->toToggle(),
            ModuleParameterEnum::VaultSafe->toToggle(),
            ModuleParameterEnum::VaultPasswordGenerator->toToggle(),
        ];
    }
}
```

**Points clés :**
- `ModuleToggleProviderInterface::getToggles(): list<ModuleToggle>` — agrégé
  par `ModuleToggleRegistry` et consommé par `ModuleAccessChecker` (global +
  per-user + cascade) et `UsersViewBuilder` (picker UI).
- `ModuleToggle` : `{key, labelKey, descriptionKey, parent?}` — le `parent`
  matérialise la cascade (un enfant est OFF si le parent est OFF).
- **Aurora-client peut implémenter `ModuleToggleProviderInterface` sans patch
  sur core** pour brancher ses propres toggles.

### 5.4 Précédent canonique

Voir `src/Module/Vault/VaultModule.php` (en commit courant). Avant la fusion
PasswordGenerator → Vault (commit `dee99658`), le pattern était documenté via
un module standalone PasswordGenerator (commit `167aafa`) — historiquement
utile mais plus représentatif aujourd'hui : **Vault est l'exemple à jour**.

---

## 6. Cas 3 — module avec entités CRUD

Pour un module avec persistance Doctrine + UI CRUD, suivre **en plus** des
cas 1-2 :

### 6.1 Convention extensibilité 5 couches

Tout entité backend CRUD suit le pattern Sylius-style :
**Entity (Interface + Abstract + concrete non-final) → DTO (Input/Factory) →
Manager (Interface + hooks `protected`) → Serializer → Vue (`extraFields` + slots)**.

Doc canonique : [`entity_extensibility_convention.md`](entity_extensibility_convention.md).

### 6.2 `resolve_target_entities`

Une seule ligne manuelle à ajouter par entité dans
`AuroraBundle::$resolve_target_entities` (lignes 233-319 environ) :

```php
public static array $resolve_target_entities = [
    // …
    MyEntityInterface::class => MyEntity::class,
];
```

**Note** : Doctrine `resolve_target_entities` ne s'applique qu'aux **relations
Doctrine**, pas aux `new MyEntity()` directs. Pour permettre au client de
substituer la classe instantiée, exposer un hook `protected createMyEntity(): MyEntityInterface`
dans le Manager (cf. §3 de la convention extensibilité).

### 6.3 Sequence Postgres

```php
#[ORM\GeneratedValue(strategy: 'SEQUENCE')]
#[ORM\SequenceGenerator(sequenceName: 'seq_core_<entity>_id')]
```

Préfixe `seq_core_` **obligatoire** pour éviter les collisions avec des
entités client homonymes.

### 6.4 Repository

```php
class MyEntityRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MyEntityInterface::class);
    }
}
```

**Jamais** `ServiceEntityRepository` directement (sinon le `resolve_target_entities`
ne fonctionne pas). Cf.
[`decision_repository_no_interface.md`](../../../.claude/memory/aurora-core/architecture/decision_repository_no_interface.md)
pour la décision "pas d'interface Repository".

---

## 7. Cas 4 — module avec frontend public

Pour un module qui expose des pages publiques (pas que back-office), créer
**`<Module>FrontendDescriptor.php`** à la racine du module :

```php
// src/Module/Photo/PhotoFrontendDescriptor.php
namespace Aurora\Module\Photo;

use Aurora\Core\Frontend\Contract\FrontendInterface;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

final class PhotoFrontendDescriptor implements FrontendInterface
{
    public function getSlug(): string             { return 'photo'; }
    public function getLabel(): string            { return 'Photo'; }
    public function getHomeRoute(): string        { return 'frontend_gallery'; }
    public function getPriority(): int            { return 3; }
    public function getModuleSettingKey(): string { return ModuleParameterEnum::PhotoFrontend->value; }
    public function getRoutePrefixes(): array     { return ['frontend_gallery']; }
}
```

**Règles :**
- Convention nom : `<Module>FrontendDescriptor` à la racine `src/Module/<Module>/`
  (symétrie avec les autres modules : Ged, Photo, Editorial, Ecommerce).
- `getModuleSettingKey()` pointe vers l'enum `ModuleParameterEnum::<Module>Frontend`
  — toggle dédié frontend, distinct du toggle backend.
- `FrontendRouteGateSubscriber` 404 automatiquement les routes du frontend
  désactivé (matche par `getRoutePrefixes()`).

Doc référence :
[`pattern_frontend_descriptor.md`](../../../.claude/memory/aurora-core/architecture/pattern_frontend_descriptor.md)
+ [`pattern_frontend_toggle.md`](../../../.claude/memory/aurora-core/architecture/pattern_frontend_toggle.md).

---

## 8. Cas 5 — module avec settings (onglet Configuration)

Pour contribuer un onglet à la page admin Settings, implémenter
**`ConfigurationTabProviderInterface`** :

```php
// src/Module/MyModule/Setting/MyModuleConfigurationTabProvider.php
namespace Aurora\Module\MyModule\Setting;

use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTab;
use Aurora\Module\Configuration\Setting\Configuration\ConfigurationTabProviderInterface;
use Aurora\Module\Configuration\Setting\Configuration\SettingFieldDescriptor;

final readonly class MyModuleConfigurationTabProvider implements ConfigurationTabProviderInterface
{
    private const array TAB_PRIORITY = [
        'my_module' => 120,
        // 'shared_tab_id' => 90,  // pour contribuer à un onglet partagé (ex. 'sequences')
    ];

    public function getTabs(): array
    {
        $fieldsByGroup = [];
        foreach (MyModuleSettingEnum::cases() as $case) {
            $fieldsByGroup[$case->getGroup()][] = new SettingFieldDescriptor(
                key: $case->getKey(),
                type: $case->getType(),
                labelKey: $case->getLabel(),
                descriptionKey: $case->getDescription(),
                defaultValue: $case->getDefaultValue(),
            );
        }

        // Construire les ConfigurationTab à partir des fields groupés
        // (cf. CrmConfigurationTabProvider pour le pattern complet)
    }
}
```

**Règles :**
- Un module peut contribuer à **plusieurs onglets** (son onglet dédié + des
  onglets partagés comme `sequences` qui agrège les préfixes de référence de
  tous les modules).
- `SettingDefinitionRegistry` agrège tous les providers et merge par `id`
  d'onglet — pas de patch sur core nécessaire.
- Enum dédié `MyModuleSettingEnum` (cases avec `getKey/getType/getLabel/getDescription/getDefaultValue/getGroup`).

Doc référence :
[`pattern_configuration_tab_provider.md`](../../../.claude/memory/aurora-core/architecture/pattern_configuration_tab_provider.md).

---

## 9. Icônes de navigation

Les icônes nav sont des **chaînes kebab-case** résolues via `ICON_MAP` dans
`src/Core/Frontend/backend/sidemenu/composables/useSidemenuNav.js`. Si l'icône
manque → fallback automatique sur `FileText`.

Pour ajouter une nouvelle icône :

```js
// src/Core/Frontend/backend/sidemenu/composables/useSidemenuNav.js
import { KeyRound } from 'lucide-vue-next';

const ICON_MAP = {
    // …
    'key-round': KeyRound,
    vault: Lock,
};
```

Bibliothèque : [Lucide](https://lucide.dev/icons/) (`lucide-vue-next`).
Choisir une icône, ajouter l'import + l'entrée `'kebab-name': IconComponent`.

---

## 10. Checklist récap — nouveau module

Pour un module **avec entités CRUD + frontend + settings + sous-features
togglables** (cas le plus complet) :

> **Raccourci** : `php bin/console aurora:make:module <Module>` couvre les
> étapes 1, 2, 7, 8, 9, 11 (et 12-13 avec `--with-frontend` / `--with-settings`).
> Cf. section 2 ci-dessus pour les détails. La checklist ci-dessous reste la
> référence si tu construis à la main ou complètes après scaffold.

1. [ ] `src/Module/<Module>/<Module>Module.php` (`ModuleInterface` +
   `ModuleToggleProviderInterface` par défaut)
2. [ ] `src/Module/<Module>/<Module>Context.php` (à la racine du folder
   du module — pas sous `Service/` depuis 0.4 ; cf.
   [`pattern_core_submodules_split.md`](../../../.claude/memory/aurora-core/architecture/pattern_core_submodules_split.md))
3. [ ] Ajouter les cases dans
   `src/Module/Configuration/Setting/Enum/ModuleParameterEnum.php`
   (avec cascade graph parent/enfant)
4. [ ] Entités : `Entity/<Name>Interface` + `Abstract<Name>` (MappedSuperclass) +
   `<Name>` non-`final` avec sequence `seq_core_<entity>_id`
5. [ ] Ajouter au `resolve_target_entities` de `AuroraBundle.php` (une ligne
   par entité)
6. [ ] DTO + Manager + Serializer + Repository (convention 5 couches —
   cf. [`entity_extensibility_convention.md`](entity_extensibility_convention.md))
7. [ ] `Controller/Backend/<Name>Controller.php` (type-hint les **interfaces**,
   pas les classes concrètes)
8. [ ] `src/Module/<Module>/templates/backend/*.html.twig`
9. [ ] `src/Module/<Module>/assets/backend/*.vue` (avec `extraFields` + slots
   scoped pour extensibilité Vue)
10. [ ] `aliases.js` : `"@<module-kebab>": moduleAlias("<Module>")`
11. [ ] `src/Module/<Module>/translations/messages.{fr,en}.yaml`
12. [ ] `<Module>FrontendDescriptor.php` (si front public)
13. [ ] `Setting/<Module>ConfigurationTabProvider.php` + `Setting/<Module>SettingEnum.php`
   (si settings)
14. [ ] Tests verts + build OK + commit atomique

**Auto-discoveries qui marchent sans toi** : Doctrine mappings, Twig namespaces,
Symfony translator paths, `DumpJsTranslationsCommand`, services tagging,
configuration tab merging, frontend toggle gating, Vue components glob.

---

## 11. Pièges connus

- **`readonly class`** PHP 8.2+ force tout enfant à être également `readonly` →
  les classes destinées à être étendues (Entity, DTO, Manager, Serializer) ne
  doivent **jamais** être `readonly class`. Préférer
  `class { public readonly … }` par propriété.
- **Controllers ≠ `readonly`** : `setContainer()` est appelé après `__construct`.
  Toujours `final class` (sans `readonly`).
- **Permissions sub-features** : `<parent_module>.<feature>.use`, jamais
  `<feature>.use` (cf. fusion PasswordGenerator → `vault.password_generator.use`).
- **`#[AsAlias]` sur l'interface** : permet la substitution client mais la
  décoration ne fonctionne que si les consommateurs type-hint l'**interface**,
  pas la classe concrète.
- **Sub-DTO** (ex. `<Name>TranslationInput` dans `<Name>Input`) : restent
  `final readonly`, **pas instrumentés**. Seul le DTO racine consommé par le
  controller a une factory + interface.
- **Templates co-localisés au module** : `src/Module/<Module>/templates/` —
  parallèle à `assets/` et `translations/`. L'auto-discovery globe
  `src/Module/*/templates/` (cf. `AuroraBundle::prependExtension`).
  Les clients peuvent override soit via le même chemin
  (`src/Module/<Module>/templates/` côté projet client) soit via le legacy
  `templates/Module/<Module>/` à la racine du projet client (toujours
  supporté pour backward compat).
