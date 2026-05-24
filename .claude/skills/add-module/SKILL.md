---
name: add-module
description: Scaffold a new Aurora module from scratch. Drives the `aurora:make:module` Symfony wizard with the right flags, then handles the post-scaffold edits the CLI can't make on its own — `ModuleParameterEnum` case insertion (core), `aliases.js` entry (core), a context-appropriate Lucide icon, and polished FR/EN translation labels. Use when the user asks to "create", "add", "scaffold", "ajouter", "créer", "générer" a new module ("nouveau module"). Stops short of entity scaffolding (defers to `/add-entity`).
scope: shared
---

# add-module

Thin orchestrator around `bin/console aurora:make:module`. The wizard owns
the file generation (templates live in
`src/Core/Module/Command/templates/*.tpl`); this skill picks the right
flags, then makes the surgical edits the CLI doesn't do — patching
`ModuleParameterEnum`, appending to `aliases.js`, choosing a meaningful
icon, polishing translations.

> Conventions doc canonique: `docs/aurora-core/dev/add_module.md` (core)
> ou `docs/aurora-client/extending/add_module.md` (client).
> Pour les sub-features d'un module existant : `/add-submodule`.

## Step 0 — Detect context (CORE vs CLIENT)

```bash
grep '"name":' composer.json | head -1
#   → "axelraboit/aurora"        → CORE
#   → anything else (and require lists `axelraboit/aurora`) → CLIENT
```

If unclear, **stop and ask**. The wizard does the same detection
internally; this check is to know whether you'll need to patch
`ModuleParameterEnum` (CORE only) afterwards.

## Required inputs (ask upfront if missing)

1. **Module name** in PascalCase (`Tracking`, `Loyalty`, `WikiNotes`).
2. **Layer flags** :
   - `--no-toggle` : infra-only modules that must always be on
     (Dev-style). Skip the Context + ModuleToggleProviderInterface.
   - `--with-frontend` : public-facing module (adds FrontendDescriptor).
   - `--with-settings` : own admin Settings tab (adds SettingEnum +
     ConfigurationTabProvider).
   Defaults: toggle ON, frontend OFF, settings OFF.
3. **NavSection priority** (default 60). Lower = higher in sidemenu.
   Match the neighbour modules' priorities for visual grouping.
4. **Icon** (kebab-case Lucide name) — pick one that fits the module's
   purpose (`flame` for welding, `key-round` for vault, `clipboard-check`
   for workflows, `wallet` for finance…). The wizard hardcodes `flame`
   as a placeholder; you'll override it in `<Module>Module.php` after
   the wizard runs.

For CRUD entities, **do not** generate them here — defer to
`/add-entity` after the module skeleton lands.

## Step 1 — Run the wizard

```bash
bin/console aurora:make:module <Module> [--no-toggle] [--with-frontend] [--with-settings]
```

Pipe the answers if the prompts are still asked (priority, etc.). The
wizard generates:

- `<Module>Module.php` (togglable variant or `--no-toggle` variant)
- `<Module>Context.php` (only when togglable)
- `Controller/Backend/<Module>Controller.php`
- `templates/backend/index.html.twig`
- `assets/backend/<Module>App.vue`
- `translations/messages.{fr,en}.yaml`
- `Setting/<Module>SettingEnum.php` (only with `--with-settings`)
- `Setting/<Module>ConfigurationTabProvider.php` (only with `--with-settings`)
- `<Module>FrontendDescriptor.php` (only with `--with-frontend`)

All templates already wire the current conventions: `ModuleToggleProviderInterface`,
`getToggles()`, `getCatalogNavSections()` unfiltered, `ConfigurationTab::$moduleToggle`,
`SettingEnum::getPlaceholder()`. **Don't reimplement any of this in the skill —
the templates are the source of truth.**

The wizard prints a list of "next steps" at the end. **Read it
carefully** — anything it flags as needing manual action is your job
next.

## Step 2 — Post-wizard edits (the Claude-only work)

### 2a. Patch `ModuleParameterEnum` (CORE + togglable only)

File: `src/Module/Configuration/Setting/Enum/ModuleParameterEnum.php`.
The wizard prints a hint about this but doesn't do the edit — there are
five `match ($this)` expressions to extend.

Add:

```php
// Top-level case (near the other *Backend cases)
case <Module>Backend = 'modules_<module_id>_backend';
```

Then add a `match` arm in each of:
- `getLabel()` → `'backend.modules.<module_id>_backend'`
- `getDescription()` → `'backend.modules.<module_id>_backend_description'`
- `getModuleId()` → `'<module_id>'` (top-level toggles only)
- `getDefaultValue()` → `'1'`
- `getType()` → `'boolean'`
- `getGroup()` → `'modules'`
- `getPlaceholder()` → `null` (default, falls through the `default` arm)

If `--with-frontend` was used, also add `<Module>Frontend = 'modules_<module_id>_frontend'`
with the same five arms.

If `--with-settings` was used and the module has sub-features later
(rare at first scaffold), each sub-toggle adds its own enum case with
`getParentCase()` returning `self::<Module>Backend` and
`getCascadeRequires()` returning `self::<Module>Backend->value`.

After patching, run:

```bash
make sf CMD="aurora:application-parameter"
```

…which seeds the new toggle row in `core_settings` (default `'1'` = ON).

### 2b. Append to `aliases.js` (CORE only)

File: `aliases.js` at the repo root. Add `@<module-kebab>` entry pointing
at the new module's assets. Match the existing alphabetical order:

```js
"@<kebab>": resolve(__dirname, "src/Module/<Module>/assets"),
```

### 2c. Override the icon in `<Module>Module.php`

The wizard hardcodes `'flame'` in every NavItem. Change it to whatever
you picked in step 0 (input 4). If the icon isn't already in
`src/Core/Frontend/backend/sidemenu/composables/useSidemenuNav.js`
ICON_MAP, also register it there (import from `lucide-vue-next`).

### 2d. Polish the FR/EN labels

The wizard fills `messages.{fr,en}.yaml` with `{{MODULE_LABEL}}` everywhere.
Open both files and write proper sentences for:
- `backend.modules.<module_id>` — module display name
- `backend.modules.<module_id>_description` — one-line description shown
  on `/dev/dashboard/modules` and `/backend/settings`
- `backend.nav.<module_id>` — sidemenu label
- `backend.nav.<module_id>_description` — tooltip on the nav item
- `<module_id>.title` — page H1 in the Vue entrypoint

Same wording quality the user expects (action-oriented FR, neutral
English), not the placeholder string.

### 2e. Client-only manual wiring

CLIENT projects don't inherit aurora-core's auto-discovery globs. Verify
(and edit if missing) :

1. `config/services.yaml` — `_instanceof: ModuleInterface: tags: [aurora.module]`
   must already exist. If not, point the user to add it.
2. `config/packages/twig.yaml` — append the namespace path :
   ```yaml
   twig:
       paths:
           '%kernel.project_dir%/src/Module/<Module>/templates': '<Module>'
   ```
3. `config/services.yaml` — append the translations dir to
   `DumpJsTranslationsCommand.$extraSourceDirs`.

The wizard prints these in its "next steps" output — quote them back to
the user if you're not sure which apply.

## Step 3 — Sync + verify

```bash
make module-sync    # privileges:sync + menus:sync + application-parameter + translation + build
make ft             # tests + lint
```

If the user mentioned an entity (e.g. "create the Loyalty module with a
Reward entity"), chain to `/add-entity` after `make ft` is green.

## Boundaries

- **Read-only on entities** — never scaffold `<Name>.php` here. Defer
  to `/add-entity` (it asks for field types).
- **One module per invocation.** If the user wants two modules, ask
  which to start with.
- **Don't reimplement the templates.** Every file generated lives in
  `src/Core/Module/Command/templates/*.tpl`. If a template needs to
  change (e.g. new convention added on `SettingFieldDescriptor`), edit
  the template, not the skill — and the wizard will pick it up
  automatically on the next run.
- **Don't generate toggles for sub-features.** Add them later via
  `/add-submodule` (it cascades the parent toggle key correctly).
- **Always summarise** at the end: list every file created (wizard) and
  every file edited (you), with line ranges for edits. Cite the
  `printNextSteps` hints the wizard surfaced.
- **Never `--no-verify`** or skip hooks.
- **Apply the doc-audit convention** (cf.
  `process_doc_audit_before_commit.md`) : if the new module touches a
  documented topic, audit related docs/memories before committing.
