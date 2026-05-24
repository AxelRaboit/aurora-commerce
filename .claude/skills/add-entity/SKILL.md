---
name: add-entity
description: Scaffold a new Aurora entity with the 5-layer Sylius pattern. Drives the `aurora:make:entity` Symfony wizard with the right flags, then handles the post-wizard steps the CLI can't make on its own — fleshing out the AbstractX columns from the user's field list, designing the index ViewBuilder, writing FR/EN translations for the new admin page, and optionally chaining to `/add-crud-list-ui` for the Vue UI. Use when the user asks to "create", "add", "scaffold", "générer", or "ajouter" a new entity, especially when they mention a backend CRUD page.
scope: core-only
---

# add-entity

Thin orchestrator around `bin/console aurora:make:entity`. The wizard owns
file generation (templates in `src/Core/Module/Command/templates/entity/*.tpl`)
and the surgical patch on `src/AuroraBundle.php` (`resolve_target_entities`
+ matching `use` imports). This skill drives the wizard with sensible
flags, then handles the design work the CLI can't do — translating the
user's field list into Abstract/DTO/Manager bodies, drafting the
translations, suggesting a ViewBuilder shape.

> Doc canonique : `docs/aurora-core/dev/entity_extensibility_convention.md`.
> Pour étendre une entité existante depuis un client : `/extend-aurora-entity`.
> Pour la UI list page une fois l'entité scaffoldée : `/add-crud-list-ui`.

## Required inputs

1. **Entity name** in PascalCase (`Workspace`, `Refund`, `AuditLog`). Used
   as `<Name>`. Plural derives from `<Name>s` unless the user gives an
   irregular form (`Taxonomy` → `Taxonomies`).
2. **Module path** relative to `src/` :
   - `Core` (a core/global entity living directly under `src/Core/<Feature>/`)
   - `Module/<Module>` (e.g. `Module/Billing`, `Module/Editorial`)
   - The path must already exist. If not, stop and point at
     `bin/console aurora:make:module` or `/add-module` first.
3. **Backend CRUD?** (default yes). If no, pass `--no-crud` to the wizard —
   it generates only Layer 1 (Entity triplet + Repository), useful for :
   - Translation pivot tables (`PostTranslation`, `CommentTranslation`).
   - Audit log rows (`AuditLog`).
   - Sub-entities managed by a parent (`OrderLine` driven by
     `OrderManager` — Layer 2-5 live on the parent).
4. **Fields** — the columns the user wants on `AbstractX`. The wizard
   scaffolds with a single `name: string(150)` placeholder; you fill the
   real fields in step 2a below. **Never invent fields** — ask explicitly
   for the list.
5. **Permission** (optional override) — defaults to
   `<module_id>.<plural_snake>.manage`. Ask only if the user mentioned a
   non-standard permission scheme.
6. **Audit channel** (optional override) — defaults to `core`. The
   `AuditLogger` channel string used by the Manager (`agency.created` →
   logged under `core/agency.created`).

## Step 1 — Run the wizard

```bash
bin/console aurora:make:entity <Name> --module=<ModulePath> \
    [--no-crud] [--skip-controller] [--plural=<Irregular>] \
    [--permission=...] [--audit-channel=...]
```

Generates 13 files when CRUD is enabled (4 when `--no-crud`) plus patches
`src/AuroraBundle.php`. The wizard prints a `Next steps` block at the end
— quote it back to the user verbatim, then execute the post-wizard work
described below.

## Step 2 — Post-wizard work (the Claude-only steps)

### 2a. Flesh out AbstractX with the real fields

The wizard ships `Abstract<Name>.php` with a single `name: string(150)`
placeholder column. Edit it to add every field the user listed :

- For each field, add a `protected <type> $<name>;` with the right
  `#[ORM\Column(...)]` attribute (length / nullable / unique / enum
  type / etc.).
- Generate matching getter + setter following the existing pattern
  (return `static` on setters for the fluent-chain convention).
- Reflect each non-id field in `<Name>Interface` so client extensions
  can rely on the contract.
- If a field needs validation, add the `#[Assert\*]` constraints in
  `<Name>Input.php` AND adjust `<Name>InputInterface.php` to expose the
  new getter.

### 2b. Patch the Input + Factory for the new fields

The wizard ships `<Name>Input.php` with only `name` constructed. For each
extra field :

- Add it to the `__construct` signature as `public readonly <type> $<name>`,
  with `#[Assert\*]` constraints.
- Add the matching `get<Name>(): <type>` method.
- Reflect the getter in `<Name>InputInterface.php`.
- Extend `<Name>InputFactory::fromArray()` to read the field from the
  payload via `Str::trimFromArray($data, '<name>')` for strings (or the
  appropriate parser for other types).

### 2c. Adjust the Manager body

In `<Name>Manager::applyInput()`, mirror the field hydration :

```php
$entity->set<Field>($input->get<Field>());
```

If the entity has computed fields (slugs, timestamps, references), add
the matching logic next to `applyInput` or in a private helper.

For the `auditPayload()` body, add the new fields to the structured
payload so audit log entries carry the full state.

### 2d. Extend the Serializer

In `<Name>Serializer::serialize()`, add every new field to the returned
array. Format dates as `DATE_ATOM` (already pre-imported at the top of
the file).

### 2e. Add the index ViewBuilder

The wizard scaffolds a Controller whose `index()` method renders a flat
array of serialized rows as a placeholder. For anything more structured
(pagination, filters, joined data), create :

```
src/<ModulePath>/<Name>/View/<Plural>ViewBuilder.php
```

Pattern : take the `<Plural>Repository`, return `['<plural_snake>' =>
[serialized rows], <other index-page payload>]`. Use the existing
`AgenciesViewBuilder` as a reference. Then update the controller's
`index()` to call `$this->viewBuilder->indexView()`.

### 2f. Translations

Add the new keys in both `messages.fr.yaml` and `messages.en.yaml` under
the module's translations file :

```yaml
backend:
    nav:
        <plural_snake>: <Display name>
        <plural_snake>_description: <Tooltip>
    permissions:
        names:
            <module_id>:
                <plural_snake>:
                    manage: <Permission label>
    <plural_snake>:
        title: <Page title>
        col_name: <Name column header>
        col_actions: <Actions header>
        add: <New button>
        empty: <Empty state>
        deleted: <Toast on delete success>
        errors:
            name_required: <…>
            name_too_long: <…>
```

The error keys MUST match what the wizard's Input scaffold references
(`backend.<plural_snake>.errors.name_required` etc.) — keep them in sync.

### 2g. Twig template + Vue list page

Both are out of the wizard's scope (template structure depends on the
real fields). After fleshing out the backend :

- **Twig** : create `src/<ModulePath>/templates/backend/<plural_snake>/index.html.twig`
  extending the standard layout, mounting the Vue component via
  `vue_component('<module_id>/backend/<Plural>App', {})`.
- **Vue** : run `/add-crud-list-ui` to scaffold `<Plural>App.vue` +
  `use<Plural>Form.js` with the toolbar / table / modals following the
  Aurora convention.

## Step 3 — Migration + verify

```bash
make migration                              # generates src/Migrations/Version*.php
# Review the SQL by hand. Adjust if Doctrine's diff is over-eager
# (foreign keys, default values, etc.).
make migrate                                # applies
make cc                                     # cache:clear (mandatory after #[AsAlias])
php bin/console doctrine:schema:validate    # sanity check
make ft                                     # tests + lint
```

If the user mentioned a CRUD UI follow-up, chain to `/add-crud-list-ui`
once `make ft` is green.

## Boundaries

- **One entity per invocation.** The wizard patches AuroraBundle in
  series; batching two entities would interleave the resolve_target_entities
  edits and require manual reordering.
- **Never run migrations or apply schema changes** — leave `make migrate`
  to the user after they've reviewed the SQL.
- **Never invent fields.** Ask the user for the list. The wizard ships
  the `name: string(150)` placeholder explicitly so you don't have to
  decide a column out of thin air.
- **Don't reimplement the templates.** Every file lives in
  `src/Core/Module/Command/templates/entity/*.tpl`. If a convention
  evolves (new Manager hook, new DTO field, etc.), edit the template,
  not the skill — and the wizard picks it up automatically on the next
  run.
- **Sub-DTOs stay `final readonly`.** Only the root DTO consumed by the
  controller gets the full quartet. If the entity has sub-DTOs (e.g.
  `PostTranslationInput` nested inside `PostInput`), generate them as
  `final readonly class` without an Interface / Factory.
- **No new module creation.** If the module path doesn't exist, stop and
  point at `aurora:make:module` / `/add-module`.
- **Apply the doc-audit convention** (cf.
  `process_doc_audit_before_commit.md`) : the new entity probably belongs
  in the "instrumented entities" table in `entity_extensibility_convention.md`
  — append the row.
