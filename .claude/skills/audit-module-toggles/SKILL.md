---
name: audit-module-toggles
description: Audit every Aurora module against the module-toggle convention — does each one declare a `ModuleParameterEnum` case (or a client `getToggles()`), implement `ModuleToggleProviderInterface`, gate `getNavSections()` on `<Module>Context::isBackendEnabled()`, expose `getCatalogNavSections()` unfiltered, register every NavItem as a sub-toggle, and gate its `ConfigurationTab` via `moduleToggle:`? Use when the user asks to "check", "audit", "vérifier", "valider" the toggle wiring; or to find which modules are missing from `/dev/dashboard/modules` ("quels modules sont mal câblés ?", "tous les modules ont-ils les toggles ?"). Read-only — reports gaps and points at `/register-module-toggle` for the fix.
scope: shared
---

# audit-module-toggles

Mechanically check every module under `src/Module/` (or `src/Core/`) for the
module-toggle convention introduced by:

- `Aurora\Core\Module\Contract\ModuleToggleProviderInterface` — declares
  `getToggles(): list<ModuleToggle>` on the module class.
- `Aurora\Core\Module\Toggle\ModuleToggle` — value object the registry
  collects to render `/dev/dashboard/modules`.
- `Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum` — central
  enum that carries the cascade graph, labels, descriptions, and
  `getModuleId()` for core modules.
- `Aurora\Module\Configuration\Setting\Configuration\ConfigurationTab::$moduleToggle`
  — optional field that hides a Settings tab when its owning module is
  disabled.

The canonical fix-up procedure is `/register-module-toggle`. This skill
only **audits** — it doesn't edit code.

## Inputs

- **Module name** (optional, PascalCase, e.g. `Notes`). If provided,
  audit only that module. If omitted, walk every module under
  `src/Module/*/` (and any client-side modules in the current
  project — e.g. `App\Module\<Module>` for aurora-client / aurora-welding).
- **Scope hint** (optional): `core-only`, `client-only`, or `all`
  (default `all`). Lets you narrow to one side when running inside a
  client project that has both vendor'd core modules and its own.

## Discovery

1. Glob `src/Module/*/[A-Z]*Module.php` (in aurora-core) and
   `src/Module/*/[A-Z]*Module.php` (in clients — both paths because
   `App\Module\` is the client namespace).
2. For each match, read the file just enough to extract:
   - The module's PascalCase id (from class name minus `Module` suffix)
   - The lower-case module id (via `getId(): string` body — or guess
     `snake_case` of the PascalCase id and verify)
   - Whether it implements `ModuleToggleProviderInterface`
3. Drop **infra modules** that are intentionally always-on. The current
   allowlist is:
   - `Configuration` — settings UI itself, no toggle (turning it off would
     hide the panel you'd use to turn it back on)
   - `Platform` — auth / users / permissions, must always be reachable
   - `Dev` — `/dev/dashboard/*` admin tools
   - `Media` — global media library, used cross-module
   - `General` — top-level dashboard
   For each of these, emit `⏭️ skipped (always-on infra)` and move on.

## Checks per module

Run the checks below **in order** for each non-skipped module. Use
`Read`, `Bash` (`grep`, `rg`, `find`). Do NOT modify files.

For each item: `✅` pass / `❌` fail / `⚠️` warning (e.g. optional because
the module has no settings tab).

### 1. Module class

1. `src/Module/<Module>/<Module>Module.php` exists.
2. The class `implements ModuleToggleProviderInterface` (grep the
   `implements` clause and the `use` line).
3. The class injects a `<Module>Context` in its constructor.

### 2. Toggle enum / provider wiring

For **core modules** (in aurora-core source):

4. `ModuleParameterEnum::<Module>Backend` case exists. Grep the enum
   file: `case <Module>Backend = 'modules_<module_id>_backend';`.
5. `getLabel()`, `getDescription()`, `getModuleId()` all return non-null
   for `<Module>Backend`. Confirm by inspecting the `match ($this) {…}`
   arms.
6. **For each NavItem** in `getCatalogNavSections()`, a matching
   sub-module case exists in `ModuleParameterEnum`. The convention is
   `<Module><SubName> = 'modules_<module_id>_<sub_id>'`, with:
   - `getParentCase()` returning `self::<Module>Backend`
   - `getCascadeRequires()` returning `self::<Module>Backend->value`

For **client modules** (in the current project's `App\Module\`):

7. A client enum (e.g. `<Module>ModuleParameterEnum`) implements
   `ApplicationParameterEnumInterface`, with cases mirroring the same
   shape as `ModuleParameterEnum` (one top-level `<Module>Backend` +
   one per NavItem).
8. The module's `<Module>ApplicationParameterProvider` (or equivalent)
   yields the enum cases via `getParameters()` so
   `aurora:application-parameter` seeds them.

### 3. Context + nav gating

9. `src/Module/<Module>/<Module>Context.php` exists.
10. `<Module>Context::isBackendEnabled(): bool` exists and reads
    `ModuleAccessChecker::isEnabled(<Module>Backend)` — not hardcoded
    `return true`.
11. `<Module>Module::getNavSections()` short-circuits on
    `!$<module>Context->isBackendEnabled()` (returns `[]`).
12. **Per NavItem**, `getNavSections()` checks the matching sub-toggle
    (e.g. `if ($ctx->isWorkflowsEnabled()) $items[] = $this->workflowsNavItem();`).
13. `getCatalogNavSections()` exists and returns the **full** nav-item
    list regardless of toggle state — the dashboard needs the catalog
    to display "this toggle controls these items" even when off.

### 4. `getToggles()` method

14. `<Module>Module::getToggles()` exists and returns a list of
    `ModuleToggle` instances (one per enum case the module owns).
    Cross-check: the count must match the number of `<Module>*` cases
    discovered in step 4–6 (or 7 for client modules).

### 5. Translations

15. `backend.modules.<module_id>_backend` is defined in
    `src/Module/<Module>/translations/messages.fr.yaml` AND
    `messages.en.yaml`.
16. `backend.modules.<module_id>_backend_description` is defined in
    both locales.
17. Sub-module trans keys (`backend.nav.<route_id>` +
    `backend.nav.<route_id>_description`) exist for every NavItem — the
    convention is to reuse the existing nav-label keys for the
    sub-toggle, so this should already be the case for any module that
    rendered a NavItem before.

### 6. Settings tab (only if a `ConfigurationTabProvider` exists)

18. Find `src/Module/<Module>/Setting/<Module>ConfigurationTabProvider.php`.
    If absent, skip with `⏭️ no settings tab` — that's fine.
19. If present, the provider's module-owned tab(s) must declare
    `moduleToggle:`:
    - Core modules: `moduleToggle: ModuleParameterEnum::<Module>Backend`.
    - Client modules: `moduleToggle: '<setting_key>'` (string form,
      since the client enum doesn't share a base class).
20. The shared `sequences` tab (if contributed) MUST leave
    `moduleToggle: null`. It's cross-module by design.

## Output format

End with a single Markdown table summarizing every module:

```
| Module           | Toggle | Context | Nav gate | getToggles | Trans | Settings tab |
|------------------|--------|---------|----------|------------|-------|--------------|
| Crm              | ✅      | ✅       | ✅        | ✅          | ✅     | ✅            |
| Editorial        | ✅      | ✅       | ⚠️ partial | ✅          | ✅     | ⏭️ no tab    |
| Project          | ❌ no `<Module>Backend` case | … |
| Configuration    | ⏭️ skipped (always-on infra) |
```

Below the table, list each `❌` and `⚠️` with:
- The exact file + line (or "missing file") that's wrong
- The one-line fix or `/register-module-toggle` invocation

Finish with a one-sentence summary:
- `✅ All N modules pass.` — if no `❌`
- `⚠️ N modules need attention — run /register-module-toggle on …` — if any fail

## Examples

<example>
user: "vérifie que tous les modules ont bien leurs toggles"
assistant: [audits everything; finds Vault is missing
`getCatalogNavSections()` and Erp doesn't gate `getNavSections()`]

The report would surface:
```
| Vault     | ✅      | ✅       | ✅        | ✅          | ✅     | ⏭️ no tab    |
```
with a footnote: "⚠️ Vault — `getCatalogNavSections()` returns
toggle-filtered items, should be unfiltered. Fix: copy the body of
`getNavSections()` minus the `isBackendEnabled()` short-circuit."
</example>

<example>
user: "audit le module Crm"
assistant: [audits only Crm; reports each of the 20 checks]
</example>

## Boundaries

- **Read-only.** Never run `Edit` / `Write`. If a gap is found, point
  the user at `/register-module-toggle <Module>` or `/add-submodule
  <Module> <SubName>`.
- **Don't query the DB.** Whether the `core_settings` rows are seeded
  is a separate concern (handled by `aurora:application-parameter`);
  the audit is purely about source-code conformance.
- **No false positives on infra.** Always honour the skipped-modules
  allowlist (Configuration, Platform, Dev, Media, General). Adding
  toggles to those would break the admin panel itself.
- **Sub-modules count.** If a module has `N` NavItems in its catalog
  but only `M` sub-toggles in the enum, flag the difference — that's
  the most common scaffolding gap.
