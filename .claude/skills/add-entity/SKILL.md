---
name: add-entity
description: Scaffold a new Aurora entity with the full 5-layer extensibility pattern. Use when the user asks to "create", "add", "scaffold", "générer", or "ajouter" a new entity, especially when they mention a backend CRUD page. Generates the PHP backend (Entity triplet + DTO quartet + Manager pair + Serializer pair + Repository + Controller skeleton) following Agency as the canonical template. Stops short of Vue + Doctrine migration (those need human decisions on fields/columns/UI).
---

# add-entity

Scaffold a new Aurora entity following
`docs/aurora-core/dev/entity_extensibility_convention.md`. Generates the
**backend PHP layer only** — Vue assets, Twig templates, and Doctrine
migration are out of scope (too entity-specific, must be hand-tailored).

## Required inputs (ask upfront if missing)

1. **Entity name** in PascalCase (`Invoice`, `BlogPost`, `Workspace`). Used
   as `<Name>`. Plural form is auto-derived (`s` suffix); ask if irregular
   (e.g., `Taxonomy` → `taxonomies`).
2. **Module** — must be one of:
   - `Core/<Feature>` (e.g., `Core/Workspace`)
   - `Module/<Module>/<Feature>` (e.g., `Module/Billing/Refund`,
     `Module/Editorial/Page`)
   New module? Stop and point the user to `docs/aurora-core/dev/add_module.md`
   first — this skill does not create modules.
3. **Backend CRUD?** (yes/no) — if no, generate **only Layer 1** (Entity
   triplet + Repository) and mark the rest as not-applicable. Examples of
   no-CRUD entities: `*Translation`, items/lines, audit logs, sessions —
   see convention §2.2.
4. **Fields** — the list of business columns for `AbstractX` (name, type,
   length, nullable, validation). The skill scaffolds with **a single
   `name: string(150)` column** by default if the user just wants the
   skeleton; for richer DTOs, ask.

## What gets generated

For a `<Name>` entity in `<Module>` (e.g., `Workspace` in `Core/Workspace`),
namespace `Aurora\Core\Workspace`:

### Layer 1 — Entity (always)

```
src/<Module>/Entity/
├── <Name>Interface.php       # extends TimestampableInterface, declares getters/setters
├── Abstract<Name>.php         # #[ORM\MappedSuperclass] #[ORM\HasLifecycleCallbacks], uses TimestampableTrait, all columns except id
└── <Name>.php                 # #[ORM\Entity] #[ORM\Table(name: '<table>')], non-final, id + seq_core_<snake>_id
```

Conventions:
- Table name: `core_<plural_snake>` (e.g., `core_workspaces`).
- Sequence: `seq_core_<snake>_id` — **HARD RULE**, the `core_` prefix is
  non-negotiable (cf. convention §3 layer 1).
- `<Name>` carries only `id`, `SequenceGenerator`, and any `ManyToMany`
  relations (Doctrine doesn't propagate `ManyToMany` cleanly on
  `MappedSuperclass`).
- `<Name>Interface` extends `Aurora\Core\Timestampable\TimestampableInterface`
  if `TimestampableTrait` is used (default yes).

### Layer 1bis — Repository (always)

```
src/<Module>/Repository/<Name>Repository.php
```

Extends `Aurora\Core\Repository\ResolveTargetEntityRepository`. Constructor:

```php
public function __construct(ManagerRegistry $registry)
{
    parent::__construct($registry, <Name>::class, <Name>Interface::class);
}
```

No interface for the repo (convention §3 layer-bonus: limite assumée).

### Layer 1ter — AuroraBundle wiring (always)

Append to `src/AuroraBundle.php`, inside `$resolve_target_entities`:

```php
<Name>Interface::class => <Name>::class,
```

Sort lexicographically with neighbouring entries inside the same module
block (read the file first to find the right spot).

### Layer 2 — DTO (if backend CRUD)

```
src/<Module>/Dto/
├── <Name>InputInterface.php          # getters only
├── <Name>Input.php                    # non-final, public readonly props, #[Assert\*]
├── <Name>InputFactoryInterface.php
└── <Name>InputFactory.php             # #[AsAlias(<Name>InputFactoryInterface::class)], fromArray() uses Str::trimFromArray
```

Rules:
- `<Name>Input` is `class` (NOT `final`, NOT `readonly class`) — individual
  `public readonly` per prop (cf. convention §3 layer 2 rationale).
- No static `fromArray` on `<Name>Input` — only on the factory.
- Factory uses `Aurora\Core\Support\Str::trimFromArray($data, '<field>')`
  for string parsing.

### Layer 3 — Manager (if backend CRUD)

```
src/<Module>/Manager/
├── <Name>ManagerInterface.php   # create / update / delete
└── <Name>Manager.php             # #[AsAlias(<Name>ManagerInterface::class)], non-final, DI in protected readonly
```

Manager body MUST expose:
- `protected function create<Name>(): <Name>Interface { return new <Name>(); }`
  — one such hook **per class instantiated** (no exception, cf. §3.1).
- `protected function applyInput(<Name>Interface, <Name>InputInterface): void`
  — unless qualifying for User-style variant (≥6 specialized methods, no
  simple create+update, distinct security per op — currently only `User`).
- `protected auditCreated/Updated/Deleted` + `protected auditPayload` — if
  `AuditLogger` is wired. Inline domain events (e.g., `<entity>.paid`,
  `<entity>.validated`) splat-merge `$this->auditPayload($entity)`.

Default DI for the skeleton: `EntityManagerInterface` + `AuditLogger`. Add
more constructor params only if the user asked for richer behavior.

### Layer 4 — Serializer (if backend CRUD)

```
src/<Module>/Serializer/
├── <Name>SerializerInterface.php   # serialize(<Name>Interface): array
└── <Name>Serializer.php             # #[AsAlias(<Name>SerializerInterface::class)], non-final
```

Default payload: `{ id, name, createdAt: format(DATE_ATOM) }` — adjust to
match the actual fields.

### Layer 5 — Controller (if backend CRUD)

```
src/<Module>/Controller/Backend/<Plural>Controller.php
```

Skeleton with `index`/`create`/`update`/`delete` routes under
`/backend/<plural>`. **Constructor type-hints the interfaces**, not concrete
classes (except the Repository — see convention §3 layer-bonus):

```php
public function __construct(
    protected readonly <Name>Repository $repository,
    protected readonly <Name>SerializerInterface $serializer,
    protected readonly <Name>ManagerInterface $manager,
    protected readonly <Name>InputFactoryInterface $inputFactory,
    protected readonly PayloadValidator $payloadValidator,
) {}
```

Note: a `<Plural>ViewBuilder` is referenced by the Agency controller for
the index view. Don't generate one — call it out in the wrap-up so the
user adds it manually when they design the view.

## What is NOT generated (deliberate)

Tell the user explicitly at the end:

1. **Doctrine migration** — run `php bin/console doctrine:migrations:diff`
   after the entity is in place; review the generated SQL before applying.
2. **Vue assets** (`<Plural>App.vue` + `useXxxForm.js`) — the structure
   depends on the actual fields/columns. The user should copy
   `assets/Core/backend/agencies/` as a template and adapt. Reminder of the
   required surface: prop `extraFields`, slots `extra-headers` /
   `extra-cells` / `extra-form-fields`, composable that accepts an
   `extraFields` option.
3. **Twig template** — same reason. Reference:
   `templates/Core/backend/agencies/index.html.twig`.
4. **ViewBuilder** — the controller imports `<Plural>ViewBuilder` but the
   class itself depends on what data the index view needs.
5. **Translations** — add the `backend.<plural>.*` keys in
   `translations/messages.<locale>.yaml`.
6. **Voter / security rules** — if relevant.

## Procedure

1. **Confirm inputs** with `AskUserQuestion` if anything is ambiguous (name,
   module, CRUD yes/no, irregular plural). One round of questions, not more.
2. **Read the Agency reference files** (Entity/Dto/Manager/Serializer/Repo/
   Controller) as templates. Don't reinvent — match the structure 1:1.
3. **Generate all PHP files** in parallel with `Write` calls. Use the
   actual entity name, snake_case for the sequence, plural for the table.
4. **Edit `src/AuroraBundle.php`** to add the `$resolve_target_entities`
   line in the right module block.
5. **Final report**: list every file created, the AuroraBundle edit, and
   the explicit "not generated, do this manually" punch list (the 6 items
   above). Suggest running `php bin/console cache:clear` then
   `php bin/console doctrine:schema:validate` as a quick sanity check.

## Boundaries

- **One entity per invocation.** If the user wants multiple entities, run
  the skill once per entity — don't batch (the AuroraBundle edits need to
  be sequential and verified).
- **Never run migrations or apply schema changes** — only generate code.
- **Never invent fields.** If the user says "scaffold Workspace", default
  to a single `name: string(150)` column and call it out; don't invent
  `description`, `slug`, etc.
- **Don't generate Vue/Twig/translations/migrations** — listed above.
- **Sub-DTOs stay `final readonly`** — only the root DTO consumed by the
  controller gets the full quartet (cf. convention §3 layer 2 "scope" note).
- **No new module creation.** If the module path doesn't exist, stop and
  point at `docs/aurora-core/dev/add_module.md`.
