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

The project follows the **Sylius / Symfony convention** : flat,
responsibility-based folders under `src/`. There is no `Custom/` umbrella —
overrides live next to the layer they extend.

### PHP (under `src/`)

| I want to… | Drop file at… | How it's wired |
|---|---|---|
| Add a page (controller + route) | `src/Controller/MyController.php` | `#[Route]` attribute, scanned automatically |
| Add a domain service | `src/Service/MyService.php` | autowired |
| Override an Aurora service | `src/Manager/MyAgencyManager.php` (or `Serializer/`, `Service/`, …) | `#[AsAlias(AuroraInterface::class)]` |
| Decorate an Aurora service | same folder as above | `#[AsDecorator(AuroraService::class)]` |
| Listen to an Aurora event | `src/EventListener/MyListener.php` | `#[AsEventListener(AuroraEvent::class)]` |
| Extend an Aurora entity | `src/Entity/MyAgency.php` extending `AbstractAurora<Name>` (reuse Aurora's `repositoryClass`) | `resolve_target_entities` in `config/packages/doctrine.yaml` |
| Extend an Aurora DTO | `src/Dto/MyInput.php` extending `Aurora\…\<Name>Input` | `#[AsAlias]` on the matching Factory |
| Build a feature module | `src/Module/<Name>/` mirroring Aurora's module layout | `tags: [aurora.module]` in `services.yaml` |

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
| `config/services.yaml` | PSR-4 prefix registration + service tags |
| `config/routes.yaml` | Imports Aurora routes + scans `src/Controller/` and `src/Module/` |
| `config/packages/doctrine.yaml` | DBAL URL + `App\Entity` mapping + `resolve_target_entities` |
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
│   ├── Controller/             # App\Controller\*
│   ├── Entity/                 # App\Entity\*       — entity overrides (extends AbstractAurora<Name>)
│   ├── Dto/                    # App\Dto\*          — DTO overrides
│   ├── Manager/                # App\Manager\*      — manager overrides / decorators
│   ├── Serializer/             # App\Serializer\*   — serializer overrides / decorators
│   ├── Service/                # App\Service\*      — domain services
│   ├── EventListener/          # App\EventListener\*
│   └── Module/<Name>/          # App\Module\<Name>\* — feature module
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
