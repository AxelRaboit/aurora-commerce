---
name: add-module
description: Scaffold a new Aurora module from scratch. Reads the templates under `.claude/skills/add-module/templates/`, fills placeholders, writes files, and handles the smart edits (`ModuleParameterEnum` case insertion for core, `aliases.js` entry for core, context-appropriate Lucide icon, polished FR/EN translation labels). Use when the user asks to "create", "add", "scaffold", "ajouter", "créer", "générer" a new module ("nouveau module"). Stops short of entity scaffolding (defers to `/add-entity`).
scope: shared
---

# add-module

Scaffold a new Aurora module. The skill is the **only** entry point —
the previous `aurora:make:module` CLI wizard was removed to prevent
drift (devs running it bare were skipping the `ModuleParameterEnum`
patch, the `aliases.js` append, the icon swap, the label polish).

The shape of every generated file lives in
`.claude/skills/add-module/templates/*.tpl`. The skill reads each
template, applies the placeholder substitution, and writes the result
via the `Write` tool. **Don't reimplement the file contents** — read
the template, substitute, write.

> Conventions doc canonique: `docs/aurora-core/dev/add_module.md` (core)
> ou `docs/aurora-client/extending/add_module.md` (client).
> Pour les sub-features d'un module existant : `/add-submodule`.

## Step 0 — Detect context (CORE vs CLIENT)

```bash
grep '"name":' composer.json | head -1
#   → "axelraboit/aurora"        → CORE
#   → anything else (and require lists `axelraboit/aurora`) → CLIENT
```

If unclear, **stop and ask**. The context decides namespace prefix,
sequence prefix, asset path, and which `Module.*.php.tpl` /
`Context.*.php.tpl` / `FrontendDescriptor.*.php.tpl` variant to read.

| | CORE | CLIENT |
|---|---|---|
| Namespace prefix | `Aurora\Module\<X>` | `App\Module\<X>` |
| Sequence prefix (entity later) | `seq_core_<entity>_id` | `seq_app_<entity>_id` |
| Asset path | `src/Module/<X>/assets/` | `src/Module/<X>/assets/` (same since 0.5) |
| Toggle storage | `ModuleParameterEnum::<X>Backend` enum case | `<X>Context::BACKEND_KEY` const |
| Templates root for skill reads | `vendor/axelraboit/aurora/.claude/skills/add-module/templates/` (when running from a client) OR `.claude/skills/add-module/templates/` (core) | same |

## Required inputs (ask upfront if missing)

1. **Module name** in PascalCase (`Tracking`, `Loyalty`, `WikiNotes`).
2. **Layer flags** :
   - **Toggle** (default ON) — generates `<Module>Context.php` + the
     `ModuleToggleProviderInterface` implementation on `<Module>Module`.
     Skip only for infra-only modules that must always be on
     (Dev-style).
   - **`--with-frontend`** equivalent : adds `<Module>FrontendDescriptor.php`
     (public-facing module).
   - **`--with-settings`** equivalent : adds `Setting/<Module>SettingEnum.php`
     + `Setting/<Module>ConfigurationTabProvider.php` (own admin Settings tab).
3. **NavSection priority** (default 60). Lower = higher in sidemenu. Match
   neighbour modules' priorities for visual grouping.
4. **Icon** (kebab-case Lucide name). Pick one that fits the module's
   purpose (`flame` for welding-style, `key-round` for vault,
   `clipboard-check` for workflows, `wallet` for finance, etc.) — do NOT
   default to `flame` if a better icon exists.

For CRUD entities, **do not** generate them here — defer to
`/add-entity` after the module skeleton lands.

## Step 1 — Derive the variable map

Compute these from the module name and the user's answers :

| Variable | Derivation | Example (`Loyalty`) |
|---|---|---|
| `{{MODULE}}` | PascalCase as-given | `Loyalty` |
| `{{MODULE_ID}}` | snake_case | `loyalty` |
| `{{MODULE_KEBAB}}` | kebab-case (= snake-with-dashes) | `loyalty` |
| `{{MODULE_VAR}}` | camelCase | `loyalty` |
| `{{MODULE_LABEL}}` | user-friendly label (ask if not obvious) | `Loyalty` |
| `{{ICON}}` | user input | `award` |
| `{{PRIORITY}}` | user input as string | `'60'` |
| `{{NAMESPACE}}` | `Aurora\Module\<X>` (core) or `App\Module\<X>` (client) | `Aurora\Module\Loyalty` |
| `{{MODULE_TOGGLE_LITERAL}}` | `ModuleParameterEnum::<X>Backend` (core) / `<X>Context::BACKEND_KEY` (client) / `null` (no-toggle) | `ModuleParameterEnum::LoyaltyBackend` |
| `{{MODULE_TOGGLE_USE}}` | matching `use` clause (one extra line) or empty string | `use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;\n` |

## Step 2 — Pick the right template variants

Module class :
- togglable + CORE → `Module.core.php.tpl`
- togglable + CLIENT → `Module.client.php.tpl`
- `--no-toggle` → `Module.NoToggle.php.tpl`

Context (only when togglable) :
- CORE → `Context.core.php.tpl`
- CLIENT → `Context.client.php.tpl`

FrontendDescriptor (only when `--with-frontend`) :
- CORE → `FrontendDescriptor.core.php.tpl`
- CLIENT → `FrontendDescriptor.client.php.tpl`

Settings (only when `--with-settings`, both core + client share these) :
- `SettingEnum.php.tpl`
- `ConfigurationTabProvider.php.tpl`

Always :
- `Controller.php.tpl`
- `index.html.twig.tpl`
- `App.vue.tpl`
- `messages.fr.yaml.tpl`
- `messages.en.yaml.tpl`

## Step 3 — Read, substitute, write

For each chosen template :

1. `Read` the file at `.claude/skills/add-module/templates/<template>.tpl`
   (from the cwd — when running in a client, the path resolves to the
   symlinked vendor copy).
2. Apply variable substitution (just `str_replace` style — every
   `{{KEY}}` token is mechanical).
3. `Write` the result to the target path :

| Template | Target |
|---|---|
| `Module.{core,client,NoToggle}.php.tpl` | `src/Module/<Module>/<Module>Module.php` |
| `Context.{core,client}.php.tpl` | `src/Module/<Module>/<Module>Context.php` |
| `FrontendDescriptor.{core,client}.php.tpl` | `src/Module/<Module>/<Module>FrontendDescriptor.php` |
| `SettingEnum.php.tpl` | `src/Module/<Module>/Setting/<Module>SettingEnum.php` |
| `ConfigurationTabProvider.php.tpl` | `src/Module/<Module>/Setting/<Module>ConfigurationTabProvider.php` |
| `Controller.php.tpl` | `src/Module/<Module>/Controller/Backend/<Module>Controller.php` |
| `index.html.twig.tpl` | `src/Module/<Module>/templates/backend/index.html.twig` |
| `App.vue.tpl` | `src/Module/<Module>/assets/backend/<Module>App.vue` |
| `messages.fr.yaml.tpl` | `src/Module/<Module>/translations/messages.fr.yaml` |
| `messages.en.yaml.tpl` | `src/Module/<Module>/translations/messages.en.yaml` |

## Step 4 — The smart post-edits (Claude-only work)

These are NOT templates — they're context-sensitive edits to existing
files. The previous CLI wizard merely printed hints about them; that's
exactly the gap this skill closes.

### 4a. Patch `ModuleParameterEnum` (CORE + togglable only)

File: `src/Module/Configuration/Setting/Enum/ModuleParameterEnum.php`.

Add:

```php
// Top-level case (near other *Backend cases)
case <Module>Backend = 'modules_<module_id>_backend';
```

Then extend five `match ($this)` expressions :
- `getLabel()` → `'backend.modules.<module_id>_backend'`
- `getDescription()` → `'backend.modules.<module_id>_backend_description'`
- `getModuleId()` → `'<module_id>'` (top-level toggle only)
- `getDefaultValue()` → `'1'`
- `getType()` → `'boolean'`
- `getGroup()` → `'modules'`
- `getPlaceholder()` → default arm already returns `null` (no change)

If `--with-frontend` was used, also add `<Module>Frontend = 'modules_<module_id>_frontend'`
with the same arms.

Run afterwards:

```bash
make sf CMD="aurora:application-parameter"
```

…which seeds the new toggle row in `core_settings` (default `'1'` = ON).

### 4b. Append to `aliases.js` (CORE only)

File: `aliases.js` at the repo root. Add `@<module-kebab>` in
alphabetical order :

```js
"@<kebab>": moduleAlias("<Module>"),
```

### 4c. Polish the FR/EN labels

The template fills `messages.{fr,en}.yaml` with `{{MODULE_LABEL}}` plus
short placeholders. Open both files and write proper sentences for :
- `backend.modules.<module_id>` — module display name
- `backend.modules.<module_id>_description` — one-line description shown
  on `/dev/dashboard/modules` and `/backend/settings`
- `backend.nav.<module_id>` — sidemenu label
- `backend.nav.<module_id>_description` — sidemenu tooltip
- `<module_id>.title` — page H1 in the Vue entrypoint

Action-oriented French, neutral English. Don't leave the literal
`{{MODULE_LABEL}}` if the substitution missed something.

### 4d. Client-only manual wiring

CLIENT projects don't inherit aurora-core's auto-discovery globs. Verify
(and edit if missing) :

1. `config/services.yaml` — `_instanceof: ModuleInterface: tags: [aurora.module]`
   must already exist. If not, ask the user to add it.
2. `config/packages/twig.yaml` — append :
   ```yaml
   twig:
       paths:
           '%kernel.project_dir%/src/Module/<Module>/templates': '<Module>'
   ```
3. `config/services.yaml` — append the new translations dir to
   `DumpJsTranslationsCommand.$extraSourceDirs`.

## Step 5 — Sync + verify

```bash
make module-sync    # privileges:sync + menus:sync + application-parameter + translation + build
make ft             # tests + lint
```

If the user mentioned an entity (e.g. "create the Loyalty module with a
Reward entity"), chain to `/add-entity` once `make ft` is green.

## Boundaries

- **Read-only on entities** — never scaffold `<Name>.php` here. Defer
  to `/add-entity` (it asks for field types).
- **One module per invocation.** If the user wants two modules, ask
  which to start with.
- **Don't reimplement the templates.** Every file is read from a `.tpl`
  in `.claude/skills/add-module/templates/`. If a convention needs to
  change (new field, new method, new attribute), edit the template, not
  the skill.
- **Don't generate toggles for sub-features.** Add them later via
  `/add-submodule` (it cascades the parent toggle key correctly).
- **Always summarise** at the end: list every file created and every
  file edited, with line ranges for the edits (so the user can review).
- **Never `--no-verify`** or skip hooks.
- **Apply the doc-audit convention** (cf.
  `process_doc_audit_before_commit.md`) : if the new module touches a
  documented topic, audit related docs/memories before committing.
