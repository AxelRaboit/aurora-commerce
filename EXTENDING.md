# Extending Aurora

Aurora is designed to be used as a **template/core** for client applications.
Each client lives in its own git repository and consumes Aurora as a git
submodule under `vendor/aurora/`.

This document is the contract: it describes **how** clients extend Aurora
without ever modifying core files. Anything documented here is considered
part of the public extension surface and won't be broken without a major
version bump.

## High-level model

```
client-app/
├── vendor/aurora/        # this repo (submodule, read-only for the client)
├── src/Custom/           # App\Custom\* — the client's PHP code
├── templates/Custom/     # client templates that override Aurora's
├── config/
│   ├── services-custom.yaml
│   └── routes-custom.yaml
└── .env                  # overrides vendor/aurora/.env
```

Rule of thumb: **the client never edits files under `vendor/aurora/`.** All
customization happens via the extension points below. Updating Aurora is
then a one-liner (`git submodule update --remote vendor/aurora`).

## Extension points

### 1. Services

Add classes under `src/Custom/`. Register them in `config/services-custom.yaml`:

```yaml
services:
    _defaults: { autowire: true, autoconfigure: true }
    App\Custom\:
        resource: '../src/Custom/'
```

### 2. Decorating an Aurora service

Use Symfony's `#[AsDecorator]` to swap behaviour without touching core code:

```php
namespace App\Custom\Service;

use App\Core\Theme\Service\ThemeContext;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: ThemeContext::class)]
final class CustomThemeContext extends ThemeContext
{
    public function primaryColor(): string
    {
        return '#ff0066'; // client-specific brand colour
    }
}
```

### 3. Event listeners / subscribers

Hook into Aurora's flow via `#[AsEventListener]` or by implementing
`EventSubscriberInterface`. Aurora dispatches domain events for things like
user creation, post publishing, order placement, etc. (see
`src/*/Event/` for the catalog).

### 4. Routes & controllers

Place controllers under `src/Custom/Controller/`. They're picked up via
`config/routes-custom.yaml`:

```yaml
custom_controllers:
    resource:
        path: ../src/Custom/Controller/
        namespace: App\Custom\Controller
    type: attribute
```

### 5. Twig templates

Override any Aurora template by mirroring its path under `templates/Custom/`.
The client's `twig.yaml` registers `templates/Custom/` ahead of
`vendor/aurora/templates/`, so the override wins.

Example — to customize the admin layout, the client creates:

```
templates/Custom/Core/admin/layout.html.twig
```

…and Symfony resolves it before `vendor/aurora/templates/Core/admin/layout.html.twig`.

### 6. Environment variables

`.env` in the client root is loaded **after** `vendor/aurora/.env`, so any
variable redefined in the client overrides Aurora's default. Common ones:

- `APP_NAME`, `APP_SECRET`
- `DATABASE_URL`
- `MAILER_DSN`, `MAILER_FROM`, `ADMIN_EMAIL`

### 7. Theme & branding

Clients should not hardcode a brand colour in templates or CSS. Instead,
configure the active theme's `primary_color` (admin UI) — the runtime
`ThemeContext::primaryColorCss()` will regenerate the entire accent palette
(50 → 950) from that single seed.

For deeper branding (logo, footer text, header text), use the theme config
fields surfaced by `ThemeContext`.

## Updating Aurora in a client

```bash
cd client-app
git submodule update --remote vendor/aurora
git add vendor/aurora
git commit -m "chore: bump aurora to <sha>"
```

If Aurora introduces breaking changes that affect a client's customizations,
those will be called out in Aurora's `CHANGELOG.md` under a `BREAKING:` line.

## What is NOT an extension point

The following are considered internal implementation details and may change
without notice:

- Private methods of any service
- Internal CSS variable names that aren't `--th-*` or `--color-*`
- Database migration internals
- Class names of internal helpers (anything not under a `*\Contract\` or
  `*\Api\` namespace)

If a client's customization relies on one of those, it may break on a
submodule update — that's the expected trade-off.
