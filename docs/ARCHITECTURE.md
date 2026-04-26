# Velox Architecture

Velox is a platform built on Symfony 7 / PHP 8.3 / Vue 3 / Vite, designed to host
multiple independent business modules (Editorial CMS, CRM, ERP, …) on top of a shared
Core infrastructure.

---

## 1. Overview

```
src/
  Core/         -- reusable infrastructure, independent of any business module
  Module/
    Editorial/  -- editorial CMS (posts, taxonomies, comments, forms, SEO)
    Crm/        -- CRM (contacts, companies, deals, …)
    Erp/        -- ERP (products, …)

templates/
  Core/         -- admin templates for Core domains
  Module/
    Editorial/  -- admin templates for the Editorial module   (@Editorial)
    Crm/        -- admin templates for the CRM module         (@Crm)
    Erp/        -- admin templates for the ERP module         (@Erp)
  front/        -- front-end theme templates (kept flat, see §5.3)
  shared/       -- base layout, shared components, emails     (@Shared)

assets/
  Core/         -- Vue controllers and sub-components for Core domains
  Module/
    Editorial/  -- Vue controllers and sub-components for Editorial
    Crm/        -- Vue controllers and sub-components for CRM
    Erp/        -- Vue controllers and sub-components for ERP
  shared/       -- cross-cutting Vue components, composables, utils, enums
  (root)        -- entrypoints: app.js, flash.js, theme.js, admin/guest/index.js
```

---

## 2. Core (`src/Core/`)

Core contains every concern that is reusable across any business module. It must not
import anything from `App\Module\*`.

| Domain     | What it contains |
|------------|-----------------|
| User       | Entity, Managers (UserManager, FrontUserManager), Repository, DTO, Serializer, Enum (UserRole, UserStatus, UserType), Command |
| Auth       | Entities (AccessRequest, ResetPasswordRequest), Managers (AccessRequest, Invitation, PasswordReset, EmailVerification), Security (providers, checkers, authenticator, entry point), EventListeners, Validator, Controllers, DTO |
| Setting    | Entity (Setting), Repository, Enum (ApplicationParameter), Command, Controller |
| Media      | Entities (Media, MediaFolder), Manager, Repository, Service (ImageVariantGenerator), DTO, Serializer, Command, Controller |
| Theme      | Entity, Manager, Repository, Services (ThemeContext, ThemeResolver), DTO, Serializer, Controller |
| Locale     | Entity, Repository, Enum (LocaleEnum), EventSubscriber |
| Menu       | Entities (Menu, MenuItem, MenuItemTranslation), Manager, Repository, Services, DTO, Serializer, Twig extension, Command, Controller |
| Module     | ModuleInterface, ModuleRegistry, PermissionRegistry, ModulePermissionVoter, NavItem, NavSection, NavPermission — see §3 |
| Profile    | Controller |
| Search     | Service (SearchSnippetBuilder), Command, Controller |
| Dashboard  | Service (AdminStatsService), Controllers |
| Audit      | Entity (AuditLog), Service (AuditLogger) — records cross-module actions |
| Frontend   | Services (FrontContext, HttpCacheService) |
| Validation | Service (PayloadValidator), ArgumentResolver, DTO (PaginationRequest) |
| (misc)     | Twig extensions, Enums (HttpMethod), Support (Str), Trait (Timestampable), Scheduler |

### Rule

A class belongs in Core if it could be extracted into a standalone Symfony bundle and
would still make sense without any specific business module installed.

---

## 3. Module system

### 3.1 ModuleInterface

Every module must implement `App\Core\Module\ModuleInterface`:

```php
interface ModuleInterface {
    public function getId(): string;          // 'crm', 'editorial', …
    public function getNavSections(): array;  // NavSection[]
    public function getPermissions(): array;  // NavPermission[]
}
```

Modules are registered in `config/services.yaml` with the tag `velox.module`:

```yaml
App\Module\Crm\CrmModule:
    tags: [velox.module]
```

### 3.2 ModuleRegistry

`App\Core\Module\ModuleRegistry` collects all tagged modules and:
- Filters nav items by required role (via AuthorizationCheckerInterface)
- Generates Symfony route paths (via UrlGeneratorInterface)
- Aggregates permissions from all modules

The Twig function `sidebar_nav_sections()` calls the registry and returns the resolved
nav sections to the admin layout template.

### 3.3 Permission system

Each module declares named permissions via `NavPermission` objects:

```php
new NavPermission('crm.contacts.view',   UserRoleEnum::Editor->value),
new NavPermission('crm.contacts.create', UserRoleEnum::Editor->value),
new NavPermission('crm.contacts.edit',   UserRoleEnum::Editor->value),
new NavPermission('crm.contacts.delete', UserRoleEnum::Admin->value),
```

A custom Symfony Voter (`ModulePermissionVoter`) resolves these permission strings
to role checks. Controllers use `#[IsGranted('crm.contacts.view')]` instead of
`#[IsGranted('ROLE_EDITOR')]`.

---

## 4. Modules (`src/Module/<Name>/`)

Modules are organized **by domain first, by layer second**:

```
Module/<Name>/<Domain>/{Entity,Manager,Repository,DTO,Service,Serializer,Enum,Controller,…}
```

### 4.1 Module/Editorial

| Domain   | What it contains |
|----------|-----------------|
| Post     | Entities (Post, PostRevision, PostSlugHistory, PostTranslation, PostType, PostTypeField), Managers, Repository, DTO, Serializer, Enum, Services (BlocksRenderer, PostPageRenderer, PostTextExtractor), Voter, Messages + Handlers, Controllers |
| Comment  | Entities (Comment, CommentReaction), Manager + Decorator, Repository, Serializer, Enum, Service, Controllers, Contract |
| Form     | Entities (Form, FormField, FormFieldTranslation, FormSubmission, FormTranslation), Manager + Decorator, Repository, DTO, Serializer, Enum, Services, Controllers, Contract |
| Taxonomy | Entities (Taxonomy, TaxonomyTerm, translations), Manager, Repository, DTO, Serializer, Controller, Contract |
| Seo      | Services (AlternatesBuilder, RssFeedBuilder, SitemapBuilder) |
| Frontend | Controllers (HomeController, SitemapController) |

### 4.2 Module/Crm

| Domain  | What it contains |
|---------|-----------------|
| Contact | Entity, Repository, DTO (ContactInput), Serializer, Controller (admin CRUD + detail + activity timeline) |
| Company | Entity, Repository, DTO, Serializer, Controller (admin CRUD + detail) |
| Deal    | Entity, Repository, DTO, Serializer, Enum (DealStageEnum), Controller (admin CRUD + Kanban) |

### 4.3 Module/Erp

| Domain  | What it contains |
|---------|-----------------|
| Product | Entity, Repository, DTO (ProductInput), Serializer, Enum (ProductStatusEnum), Controller (admin CRUD) |

### 4.4 Module/Ecommerce

| Domain  | What it contains |
|---------|-----------------|
| Listing | Entity (FK to `Erp\Product`, slug, marketing copy, featured image, visibility, SEO), Repository, DTO (ListingInput), Serializer, Manager + Contract, Controllers (admin CRUD + public Frontend `ShopController` for `/{locale}/shop` and `/{locale}/shop/{slug}`) |

---

## 5. Conventions

### 5.1 PHP namespaces

```
App\Core\<Domain>\<Layer>\<ClassName>               -- e.g. App\Core\User\Entity\User
App\Module\<Name>\<Domain>\<Layer>\<ClassName>      -- e.g. App\Module\Crm\Contact\Entity\Contact
```

### 5.2 Templates

| Location | Twig namespace | Example |
|---|---|---|
| `templates/Core/admin/` | `@Core` | `extends '@Core/admin/layout.html.twig'` |
| `templates/Module/Editorial/admin/` | `@Editorial` | `include '@Editorial/admin/posts/index.html.twig'` |
| `templates/Module/Crm/admin/` | `@Crm` | `include '@Crm/admin/contacts/index.html.twig'` |
| `templates/shared/` | `@Shared` | `include '@Shared/components/icon.html.twig'` |

Configured in `config/packages/twig.yaml`. Add one entry per module.

### 5.3 Assets / Vue components

```
assets/Core/vue/controllers/admin/<C>.vue       → vue_component('core/admin/<C>')
assets/Module/Editorial/vue/controllers/...     → vue_component('editorial/admin/<C>')
assets/Module/Crm/vue/controllers/admin/<C>.vue → vue_component('crm/admin/<C>')
```

`assets/app.js` merges one glob per module. Add a new glob + key-replace when adding a module.

Vite aliases:

| Alias        | Path                       |
|--------------|---------------------------|
| `@`          | `assets/`                 |
| `@core`      | `assets/Core/`            |
| `@editorial` | `assets/Module/Editorial/`|
| `@crm`       | `assets/Module/Crm/`      |
| `@erp`       | `assets/Module/Erp/`      |
| `@shared`    | `assets/shared/`          |

### 5.4 Routes

Declared via `#[Route]` attributes. Auto-discovered via `services.yaml` resource `'../src/'`.

### 5.5 Doctrine

One mapping per module in `config/packages/doctrine.yaml`:

```yaml
mappings:
    AppCore:      { dir: src/Core,               prefix: 'App\Core' }
    AppEditorial: { dir: src/Module/Editorial,   prefix: 'App\Module\Editorial' }
    AppCrm:       { dir: src/Module/Crm,         prefix: 'App\Module\Crm' }
    AppErp:       { dir: src/Module/Erp,         prefix: 'App\Module\Erp' }
```

Add one mapping block per new module.

### 5.6 Adding a new module (checklist)

1. Create `src/Module/<Name>/` with domain subfolders
2. Implement `<Name>Module.php` (ModuleInterface) — declare nav + permissions
3. Tag it `velox.module` in `config/services.yaml`
4. Add Doctrine mapping to `config/packages/doctrine.yaml`
5. Add Twig namespace to `config/packages/twig.yaml`
6. Add Vue glob + alias to `assets/app.js` + `vite.config.js` + `vitest.config.js`
7. Create templates under `templates/Module/<Name>/admin/`
8. Create Vue controllers under `assets/Module/<Name>/vue/controllers/`
9. Create `src/Module/<Name>/translations/messages.{fr,en,es,de}.yaml` and register the path in `config/packages/translation.yaml` (`framework.translator.paths`); add to `SOURCE_DIRS` in `DumpJsTranslationsCommand` so the keys also flow to vue-i18n
10. Add Vue-only labels (form fields, editor blocks…) under `assets/locales/source/{locale}.js`
11. Add validator messages to `src/Core/translations/validators.*.yaml` if needed (or to the module's own file)
12. Generate + run Doctrine migration

---

## 6. Deferred decisions

### 6.1 Shared primitives extraction

`Contact` lives in `Module/Crm/`. If a future module (e.g. Editorial's "author as CRM
contact") needs the same entity, extract it to `src/Shared/<Domain>/` at that point.
Do not create a Shared primitive speculatively.

### 6.2 Translation key split (`admin.editorial.*`)

Existing Editorial keys stay flat (`admin.posts.*`, `admin.taxonomies.*`, `admin.postTypes.*`).
New modules name their keys `admin.<module>.*` from the start (already the case for CRM:
`admin.crm.deals.*`, ERP: `admin.erp.products.*`, Ecommerce: `admin.ecommerce.listings.*`).
Front keys are namespaced by feature, not module: `front.shop.*`, `front.cart.*`, etc.

### 6.3 Front-end theming per module

`templates/front/themes/<slug>/` is kept flat. ThemeResolver expects a single path per
theme. Splitting requires making ThemeResolver multi-path aware — do this when a module
needs its own front-end templates.

### 6.4 Backend-only vs front-facing modules

Each module is **backend-only by default**. A module opts into a public front by adding
a `Module/<Name>/Frontend/Controller/` directory with public routes (typically prefixed
`/{locale}/<resource>`). Conventions:

| Module     | Front? | Why |
|------------|--------|-----|
| Editorial  | ✅ Yes | A CMS exists to serve public pages |
| Ecommerce  | ✅ Yes (planned) | Catalog, cart, checkout are inherently public |
| CRM        | ❌ Never | Contacts/companies/deals are private business data |
| ERP        | ❌ Internal-only | Inventory/suppliers/costs stay backend; the public catalog lives in Ecommerce |
| Core       | n/a | Infrastructure |

### 6.5 Module dependencies & shared entities

Allowed direction of dependencies (import only downward):

```
Ecommerce  →  Erp     (catalog reads inventory)
Ecommerce  →  CRM     (Customer ↔ Contact link)
Editorial  →  (none)  — independent
CRM        →  (none)  — independent
ERP        →  (none)  — independent of business modules
All        →  Core
```

Modules **must not** depend upward (e.g. ERP must not import Ecommerce). When two
modules need the same concept, the lower module owns the canonical entity:

- `Erp\Product` is the source of truth for inventory (SKU, cost, stock, suppliers).
- `Ecommerce\Listing` (planned) references an `Erp\Product` and adds shop-only fields:
  slug, marketing description, gallery, `isVisibleOnShop`, public price, SEO. ERP
  products that aren't sold online simply have no `Listing`.

### 6.7 Translations: YAML is the source of truth, split per module

Each module owns its translations:

| Owner | Path |
|---|---|
| Core | `src/Core/translations/{messages,validators,security}.{locale}.yaml` |
| Editorial | `src/Module/Editorial/translations/messages.{locale}.yaml` |
| CRM | `src/Module/Crm/translations/messages.{locale}.yaml` |
| ERP | `src/Module/Erp/translations/messages.{locale}.yaml` |
| Ecommerce | `src/Module/Ecommerce/translations/messages.{locale}.yaml` |

Symfony's translator merges all paths automatically — keys can share top-level prefixes
(e.g. `admin.nav.*` is partially defined by every module that contributes a sidebar entry).
Paths are registered in `config/packages/translation.yaml` (`framework.translator.paths`).

**Two consumers**: Twig/PHP (Symfony's translator) and vue-i18n (Vue components). To avoid
duplicating shared keys, vue-i18n receives a deep-merge of two sources (`assets/i18n.js`):

1. `assets/locales/source/{locale}.js` — manual content for **Vue-only** keys (admin form labels,
   editor block labels, client-side validation messages…). Edit by hand.
2. `assets/locales/generated/{locale}.json` — generated from the per-module YAMLs via
   `php bin/console app:translations:dump-js`. **Gitignored.** Auto-rebuilt by `pnpm dev` /
   `pnpm build` (npm `predev` / `prebuild` hooks). YAML wins on conflict.

The dump command iterates `SOURCE_DIRS` (the same list as `framework.translator.paths`),
deep-merges each module's YAML, then converts Symfony's `%var%` placeholders to vue-i18n's
`{var}` syntax automatically — write naturally in YAML.

**Convention**: a key used in **both** Twig and Vue lives in YAML. A key used **only** in Vue
stays in the JS source. Never duplicate the same key in both files.

### 6.6 Entity naming: no module prefix on class names

PHP namespaces are the prefix. Keep entity class names clean:

```
App\Module\Ecommerce\Order\Entity\Order        ✅
App\Module\Ecommerce\Order\Entity\EcommerceOrder ❌  (verbose, redundant)
```

**Doctrine table names**, however, are always module-prefixed to avoid SQL collisions
and clarify queries in logs:

```
crm_contacts, crm_companies, crm_deals
erp_products
ecommerce_listings, ecommerce_orders, ecommerce_carts, ecommerce_customers
```

---

## 7. Roadmap

- [x] Module manifest + ModuleRegistry + dynamic admin sidebar
- [x] Module/Editorial — full editorial CMS
- [x] Module/Crm — Contact entity + admin CRUD
- [x] Permission registry (ModulePermissionVoter + per-module `#[IsGranted]`)
- [x] Module/Crm — Company entity (CRUD + detail)
- [x] Module/Crm — Deal entity + DealStageEnum (CRUD + Kanban)
- [x] Audit log / Activity timeline (Core — cross-module action logging, dev viewer)
- [x] Module/Crm — Activity timeline per contact
- [x] Shared component: AppStagePicker
- [x] Permission registry UI (read-only listing per module, dev dashboard)
- [x] Audit log filter by module (UI `<select>`)
- [x] Module/Erp — Product entity (inventory backend, admin CRUD)
- [x] Module/Ecommerce — Listing (refs Erp\Product) + admin CRUD + public Frontend (`/shop`, `/shop/{slug}`)
- [ ] Module/Ecommerce — Cart + Order + Customer (transactional, Phase 2)
- [ ] Editor.js block: ProductGrid (Editorial → Ecommerce, embed listings in posts/pages)
- [ ] ThemeResolver multi-path (per-module front templates)
