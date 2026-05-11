# Aurora Client — Quickstart cheatsheet

This document is the day-to-day reference for developers working **inside** an
Aurora client project (the project that has Aurora installed at
`vendor/axelraboit/aurora/`). It answers one question: *where do I put my
code?*

For deeper topics, follow the cross-links at the bottom.

---

## Setup (once per machine)

```bash
cp .env.local.example .env.local   # fill in your DB credentials
make install                       # composer + pnpm + DB + migrate + fixtures
make demo                          # load demo fixtures
make start                         # PHP server + Vite dev server
```

`make` lists all targets. The most useful: `start`, `demo`, `cc`, `aurora-update`.

---

## Where do I put my code?

Everything lives under `src/Module/`. The path **mirrors the Aurora namespace**
of the entity or feature being extended or created.

### PHP (under `src/Module/`)

| I want to… | Drop file at… | How it's wired |
|---|---|---|
| Add a domain service | `src/Module/<Name>/Service/MyService.php` | autowired |
| Override an Aurora service | `src/Module/Core/<Name>/Manager/<Name>Manager.php` | `#[AsAlias(AuroraInterface::class)]` |
| Decorate an Aurora service | same folder as above | `#[AsDecorator(AuroraService::class)]` |
| Listen to an Aurora event | `src/Module/<Name>/EventListener/MyListener.php` | `#[AsEventListener(AuroraEvent::class)]` |
| Extend an Aurora entity | `src/Module/<Mirror>/<Name>/Entity/<Name>.php` extending `Abstract<Name>` | `resolve_target_entities` in `config/packages/doctrine.yaml` |
| Extend an Aurora DTO | `src/Module/<Mirror>/<Name>/Dto/<Name>Input.php` | `#[AsAlias]` on the matching Factory |
| Build a new feature module | `src/Module/<Name>/` mirroring Aurora's module layout | `tags: [aurora.module]` in `services.yaml` |

**Mirror rule** — the path matches the Aurora namespace segment:

| Aurora source namespace | Client path |
|---|---|
| `Aurora\Core\Agency\…` | `src/Module/Core/Agency/…` |
| `Aurora\Module\Crm\Deal\…` | `src/Module/Crm/Deal/…` |
| `Aurora\Module\Billing\Invoice\…` | `src/Module/Billing/Invoice/…` |

### Templates (`templates/`)

| I want to… | Drop file at… |
|---|---|
| Override an Aurora template | `templates/Core/<mirrored-Aurora-path>.html.twig` *(auto-resolved before vendor's)* |
| Add a template for a feature module | `templates/Module/<Name>/...html.twig` |

### Vue (`assets/client/`)

| I want to… | Drop file at… | `vue_component()` lookup |
|---|---|---|
| Wrap an Aurora Vue component | `assets/client/Overrides/<mirrored-Aurora-path>.vue` | `<mirrored-path-without-.vue>` (no module prefix) |
| Vue components for a feature module | `assets/client/Module/<Name>/<rest>.vue` | `<name>/<rest>` |

### Config (`config/`)

| File | Purpose |
|---|---|
| `config/services.yaml` | Single `App\Module\:` PSR-4 registration + module tags |
| `config/routes.yaml` | Imports Aurora routes + scans `src/Module/` |
| `config/packages/doctrine.yaml` | DBAL URL + `AuroraClient` mapping (`src/Module/`) + `resolve_target_entities` |
| `config/packages/twig.yaml` | Strict mode in test only — paths come from Aurora |
| `config/packages/security.yaml` | Firewalls, providers — extend Aurora's defaults |

### Migrations (`migrations/`)

```bash
php bin/console doctrine:migrations:diff --namespace=ClientMigrations
# review the generated file (delete parasite SQL: seq_log, messenger_messages)
make migrate
```

---

## Project layout (reference)

```
client-app/
├── vendor/axelraboit/aurora/   # Aurora core (read-only)
├── src/
│   ├── Module/                 # App\Module\* — ALL client code
│   │   ├── Core/               #   Extensions of Aurora\Core\* entities
│   │   │   └── Agency/         #     e.g. src/Module/Core/Agency/{Entity,Dto,Manager,Serializer}
│   │   ├── Crm/                #   Extensions of Aurora\Module\Crm\* entities
│   │   ├── <Name>/             #   Client-owned feature modules (same layout as Aurora modules)
│   │   └── Tracking/           #   Example: client-specific tracking module
│   ├── Service/                # App\Service\* — cross-module stateless services (rare)
│   └── EventListener/          # App\EventListener\* — global listeners (rare)
├── templates/
│   ├── Core/                   # Override Aurora's @Core templates (auto-resolved before vendor's)
│   └── Module/<Name>/          # Module-specific templates
├── assets/client/
│   ├── Module/<Name>/          # Vue components for feature modules
│   └── Overrides/              # Vue wrappers around Aurora components
├── migrations/                 # Doctrine migrations (ClientMigrations namespace)
├── config/
│   ├── packages/               # doctrine.yaml, twig.yaml, security.yaml…
│   ├── routes.yaml
│   └── services.yaml
├── bin/console
└── public/index.php
```

---

## Going deeper

| Topic | Doc |
|---|---|
| Full extension reference (services, events, routes, entities, sequences) | [`extending_aurora.md`](./extending_aurora.md) |
| End-to-end pilot: adding a `code` field to Agency (Entity + DTO + Manager + Serializer + Vue + Twig) | [`extending_agency_pilot.md`](./extending_agency_pilot.md) |
| Aurora app architecture | [`app_architecture.md`](./app_architecture.md) |
| Form validation conventions | [`form_validation.md`](./form_validation.md) |

---

## Updating Aurora

```bash
make aurora-update
```

Runs `composer update axelraboit/aurora`. Review aurora's changelog before
pulling new versions; entity / interface signatures are stable but
breaking changes will bump the major version.
