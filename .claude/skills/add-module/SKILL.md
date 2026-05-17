---
name: add-module
description: Scaffold a new Aurora module from scratch — full 5-cas-types coverage (stateless minimal, togglable sub-features, CRUD entities, public frontend, settings tab). Use when the user asks to "create", "add", "scaffold", "ajouter", "créer", "générer" a new module ("nouveau module"). Auto-detects core vs client context (composer.json check) and adapts namespaces, sequences, asset paths accordingly. Generates `<Module>Module.php`, optional `<Module>Context`, optional `<Module>FrontendDescriptor`, optional Setting/ tab provider, Controller skeleton, Twig template, Vue entrypoint, translations. Stops short of entity scaffolding (defers to /add-entity).
scope: shared
---

# add-module

Scaffold a new Aurora module following the convention documented in
`docs/aurora-core/dev/add_module.md` (core) or
`docs/aurora-client/extending/add_module.md` (client). Generates the
minimum-viable wiring then adds optional layers (toggles, frontend,
settings) on demand.

> **Nesting convention (depuis 0.4.0)** : si le module ajoute des
> sous-features, elles vont sous `src/<root>/<Module>/<SubFeature>/`
> (Vault-style). Le `<Module>Module.php` lui-même reste à la racine.
> Cf. `.claude/memory/aurora-core/architecture/decision_core_submodule_nesting.md`.
> Pour ajouter une sous-feature à un module existant, voir `/add-submodule`.

## Step 0 — Detect context (CORE vs CLIENT)

Before doing anything else, decide which side of the ecosystem you're in :

```bash
# CORE if the repo IS aurora-core
grep '"name":' composer.json | head -1
#   → "axelraboit/aurora"        → CORE
#   → anything else, AND          → CLIENT
#   → composer.json `require` lists `axelraboit/aurora`
```

If you can't tell unambiguously, **stop and ask the user** which context
they're in. Don't guess — the namespace / prefix / asset path differences
will produce broken code if wrong.

| Detected | Namespace prefix | Sequence prefix | Asset path | Settings storage |
|---|---|---|---|---|
| CORE (business module) | `Aurora\Module\<X>` | `seq_core_<entity>_id` | `assets/Module/<X>/` | `Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum` enum case (since 0.4.0) |
| CLIENT | `App\Module\<X>` | `seq_app_<entity>_id` | `assets/client/Module/<X>/` | `<X>Context` constants (`app_<x>_<feature>`) |

## Required inputs (ask upfront if missing)

1. **Module name** in PascalCase (`Tracking`, `Loyalty`, `WikiNotes`).
   Used as `<Module>`. Auto-derives :
   - module id (snake_case for `getId()`) : `loyalty`, `wiki_notes`
   - URL prefix (kebab-case for route attributes) : `loyalty`, `wiki-notes`
   - Twig namespace (`@<Module>`) and template path
   - Sequence prefix for any entity later
2. **Cas types to scaffold** (multi-select, with cas 1 always on) :
   - **Cas 1 — stateless minimal** : always generated. Just
     `<Module>Module.php` + Controller + Twig + Vue + translations.
   - **Cas 2 — toggles + Context** : module has 2+ sub-features that should
     be enable/disable separately (e.g., Vault.Safe + Vault.PasswordGenerator).
     Adds `<Module>Context` + `ModuleToggleProviderInterface`. Triggers
     edits to `ModuleParameterEnum` (core) or just adds constants on
     Context (client).
   - **Cas 3 — entity CRUD** : module persists data. The skill **does
     NOT** generate the entity itself — it points the user to `/add-entity`
     after the module is in place. (Generating the entity requires field
     decisions out of this skill's scope.)
   - **Cas 4 — frontend public** : module exposes public-facing pages.
     Adds `<Module>FrontendDescriptor.php` implementing `FrontendInterface`.
   - **Cas 5 — settings (Configuration tab)** : module contributes an
     admin Settings tab. Adds `Setting/<Module>ConfigurationTabProvider.php`
     and `Setting/<Module>SettingEnum.php`.
3. **Nav placement** :
   - **own section** : new `NavSection('<id>', [...], priority: <N>)` —
     ask for priority (default 60)
   - **joins an existing section** (e.g., the new module is `PdfExporter`
     and joins the `editorial` section) — ask which one; the new NavItem
     must then be appended to the **owner module**'s `getNavSections()`
     (and the skill should warn the user that **two files** need editing)
4. **Icon** for the NavItem — kebab-case Lucide name (`flame`, `key-round`,
   `lightning-bolt`). If the icon isn't already in
   `assets/Core/backend/sidemenu/composables/useSidemenuNav.js` ICON_MAP,
   the skill must add it (import from `lucide-vue-next` + register).
5. **Primary permission name** — typically `<module_id>.use`. If the module
   has multiple permissions (view/create/edit/delete), ask.

## What gets generated — Cas 1 (always)

### CORE context

```
src/Module/<Module>/<Module>Module.php
src/Module/<Module>/Controller/Backend/<Module>Controller.php
src/Module/<Module>/translations/messages.fr.yaml
src/Module/<Module>/translations/messages.en.yaml
templates/Module/<Module>/backend/index.html.twig
assets/Module/<Module>/backend/<Module>App.vue
aliases.js                                  # edit: add @<kebab> entry
```

### CLIENT context

```
src/Module/<Module>/<Module>Module.php
src/Module/<Module>/Controller/Backend/<Module>Controller.php
src/Module/<Module>/translations/messages.fr.yaml
src/Module/<Module>/translations/messages.en.yaml
templates/Module/<Module>/backend/index.html.twig
assets/client/Module/<Module>/backend/<Module>App.vue
config/packages/twig.yaml                   # edit: add new namespace path
config/services.yaml                        # edit: add module's translations to $extraSourceDirs of DumpJsTranslationsCommand
```

### Snippets (apply variants per context)

**`<Module>Module.php`** — CORE example :

```php
namespace Aurora\Module\<Module>;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;

final readonly class <Module>Module implements ModuleInterface
{
    public function getId(): string { return '<module_id>'; }

    public function getPermissions(): array
    {
        return [new NavPermission('<module_id>.use')];
    }

    public function getNavSections(): array
    {
        return [
            new NavSection('<section_id>', [
                new NavItem('backend_<module_id>', 'backend.nav.<module_id>', '<icon>',
                    requiredPrivilege: '<module_id>.use',
                    descriptionKey: 'backend.nav.<module_id>_description'),
            ], priority: <N>),
        ];
    }

    public function getCatalogNavSections(): array
    {
        return $this->getNavSections();
    }
}
```

CLIENT variant : namespace `App\Module\<Module>`, otherwise identical. **Both
implement the 4 required methods** — `getId`, `getPermissions`, `getNavSections`,
`getCatalogNavSections`.

**Controller** :

```php
namespace <Ns>\Module\<Module>\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/<kebab>', name: 'backend_<module_id>')]
#[IsGranted('<module_id>.use')]
final class <Module>Controller extends AbstractController
{
    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@<Module>/backend/index.html.twig');
    }
}
```

`final class` (NOT `final readonly` — Symfony controllers call `setContainer`
after `__construct`).

**Twig template** :

```twig
{% extends '@Core/backend/layout.html.twig' %}
{% block title %}{{ 'backend.nav.<module_id>'|trans }} - {{ parent() }}{% endblock %}

{% block page_header_slot %}
    {{ include('@Shared/components/page_header.html.twig', {
        crumbs: [
            {label: 'backend.nav.sections.<section_id>'|trans},
            {label: 'backend.nav.<module_id>'|trans},
        ],
    }) }}
{% endblock %}

{% block body %}
<div {{ vue_component('<module_id_lowercase>/backend/<Module>App', {}) }} class="flex-1 min-w-0"></div>
{% endblock %}
```

**Vue entrypoint** (placeholder, user customizes) :

```vue
<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
</script>

<template>
    <div class="p-6">
        <h1 class="text-xl font-semibold">{{ t('<module_id>.title') }}</h1>
        <!-- TODO: implement -->
    </div>
</template>
```

**Translations** (`messages.fr.yaml` + `.en.yaml`) :

```yaml
backend:
    modules:
        <module_id>: <Module label FR/EN>
    nav:
        <module_id>: <Nav label>
        <module_id>_description: <Tooltip>

<module_id>:
    title: <Page title>
```

## What gets generated — Cas 2 (toggles + Context)

Adds **on top of cas 1** :

```
# CLIENT or business CORE module
src/Module/<Module>/<Module>Context.php

# Core module
src/Core/<Module>/<Module>Context.php
```

> Convention 0.4.0 : le Context vit à la **racine du folder du module**,
> à côté des sous-modules. Pas sous `Service/` (Service/ reste pour les
> vrais services métier comme Crm/Service/CrmNotificationService).

And **edits** `<Module>Module.php` to :
- implement `ModuleToggleProviderInterface` in addition to `ModuleInterface`
- inject `<Module>Context $context` via constructor
- gate `getNavSections()` with `if (!$context->isBackendEnabled()) return [];`
- add `getToggles(): array` returning at least the backend root toggle

**Context snippet (CLIENT)** :

```php
namespace App\Module\<Module>;

use Aurora\Core\Module\Service\ModuleAccessChecker;

final readonly class <Module>Context
{
    public const string BACKEND_KEY = 'app_<module_id>_backend';

    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(self::BACKEND_KEY);
    }
}
```

**Context snippet (CORE)** — keys live in `ModuleParameterEnum` :

```php
namespace Aurora\Core\<Module>;   // ou Aurora\Module\<Module> pour un business module

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class <Module>Context
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(ModuleParameterEnum::<Module>Backend);
    }
}
```

For CORE : also add the enum case to `src/Core/Setting/Enum/ModuleParameterEnum.php`
following the existing pattern (no `_enabled` suffix on the key, cf.
`.claude/memory/aurora-core/architecture/architecture_module_parameter_enum.md`).

For each sub-feature beyond the backend root, add **one more** `<KEY>` constant
+ `isXEnabled(): bool` method + `ModuleToggle` entry with
`parentKey: BACKEND_KEY` (so disabling the parent cascades).

> **For adding a sub-feature to an EXISTING module**, use `/add-submodule`
> instead — it edits the parent module / Context in place.

## What gets generated — Cas 3 (entity CRUD)

Do **NOT** generate entity files here. After cas 1 (+ optionally cas 2) is
in place, **stop and tell the user** :

> Your module skeleton is ready. To add the first entity with the
> 5-layer pattern (Interface + Abstract + concrete + DTO + Factory + Manager
> + Serializer + Repository + Controller), invoke `/add-entity` and target
> the module you just created.

This separation keeps `/add-module` predictable and lets `/add-entity` ask
its own field-level questions.

## What gets generated — Cas 4 (frontend public)

```
src/Module/<Module>/<Module>FrontendDescriptor.php
```

CORE example (Photo template) :

```php
namespace Aurora\Module\<Module>;

use Aurora\Core\Frontend\Contract\FrontendInterface;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;

final class <Module>FrontendDescriptor implements FrontendInterface
{
    public function getSlug(): string             { return '<module_id>'; }
    public function getLabel(): string            { return '<Module label>'; }
    public function getHomeRoute(): string        { return 'frontend_<module_id>'; }
    public function getPriority(): int            { return <N>; }       // ask user
    public function getModuleSettingKey(): string { return ModuleParameterEnum::<Module>Frontend->value; }
    public function getRoutePrefixes(): array     { return ['frontend_<module_id>_']; }
}
```

CLIENT : namespace `App\Module\<Module>`, `getModuleSettingKey()` returns
`<Module>Context::FRONTEND_KEY`. Both rely on `_instanceof:
Aurora\Core\Frontend\Contract\FrontendInterface: tags: [aurora.front]` in
services.yaml (must already be present client-side — cf. `add_module.md` §2.1).

CORE : also add `<Module>Frontend` case to `ModuleParameterEnum`.

## What gets generated — Cas 5 (settings configuration tab)

```
src/Module/<Module>/Setting/<Module>SettingEnum.php
src/Module/<Module>/Setting/<Module>ConfigurationTabProvider.php
```

Pattern reference : `src/Module/Crm/Setting/CrmConfigurationTabProvider.php`.
Use the **CrmConfigurationTabProvider as the canonical template** — read it
before generating to copy the exact `getTabs()` shape with `TAB_PRIORITY`
array, fields-by-group, `SettingFieldDescriptor` construction.

For CLIENT : also ensure `_instanceof:
Aurora\Core\Setting\Configuration\ConfigurationTabProviderInterface: tags:
[aurora.configuration_tab_provider]` exists in `config/services.yaml` (if
not, instruct the user to add it).

## Auto-discovery — what you DON'T need to wire (core)

CORE benefits from auto-discovery (cf. `AuroraBundle.php`) :

| Thing | Where it works automatically |
|---|---|
| Symfony service container | `Aurora\: resource: '../src/'` |
| Tag `aurora.module` | `_instanceof: ModuleInterface` in `config/services.yaml` |
| Twig `@<Module>` namespace | glob `templates/Module/<Module>/` |
| Translations | glob `src/Module/<Module>/translations/` |
| Vue components | `import.meta.glob('./Module/**/*.vue')` in `assets/app.js` |

**Only manual wiring** : `aliases.js` (one line to add for the new module).

## Manual wiring required (client)

CLIENT does NOT inherit aurora-core's globs. The skill must edit **3 files**
(or warn the user if they don't exist with the right `_instanceof` block) :

1. `config/services.yaml` — verify `_instanceof: ModuleInterface: tags: [aurora.module]`
   block exists (mandatory base wiring); add nothing if present.
2. `config/packages/twig.yaml` — append :
   ```yaml
   twig:
       paths:
           '%kernel.project_dir%/templates/Module/<Module>': '<Module>'
   ```
3. `config/services.yaml` — append to `DumpJsTranslationsCommand.$extraSourceDirs` :
   ```yaml
   - '%kernel.project_dir%/src/Module/<Module>/translations'
   ```

## Post-generation steps (always)

Print these commands at the end so the user can run them manually :

```bash
# Sync permissions + nav into the DB
make sf CMD="aurora:privileges:sync"
make sf CMD="aurora:menus:sync"

# Generate frontend translation bundle
make translation

# Clear cache after DI changes (#[AsAlias], _instanceof)
make cc

# (If cas 3 chosen and entity generated separately:)
# make migration && make migrate

# Verify everything is green
make ft
```

## Boundaries

- **Read-only on entities** — do not scaffold any `<Name>.php` entity here.
  Defer to `/add-entity`.
- **One module per invocation.** If the user wants two modules, ask which
  to start with.
- **Don't invent toggles.** If the user picks cas 2, ask them to enumerate
  the sub-features by name before generating the Context constants.
- **Don't invent fields.** No DTO / Manager / Serializer scaffolding here —
  defer to `/add-entity` (it asks for field types).
- **Always show a summary** after generation listing every file created and
  every file edited, with line numbers for edits. So the user can review.
- **Never `--no-verify`** or skip hooks.
- **Apply the doc-audit convention** (cf. `process_doc_audit_before_commit.md`) :
  if the new module touches a documented topic, audit related docs/memories
  before committing.
