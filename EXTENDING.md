# Extending Aurora

Aurora is designed to be used as a **core** for client applications.
Each client lives in its own git repository and consumes Aurora as a git
submodule under `vendor/aurora/`.

This document is the contract: it describes **how** clients extend Aurora
without ever modifying core files. Anything documented here is considered
part of the public extension surface and won't be broken without a major
version bump.

## Creating a new client project

Run the scaffold script from inside aurora-core:

```bash
bin/create-client <project-name> [destination-dir]

# Examples
bin/create-client acme-corp
bin/create-client acme-corp ~/projects/acme-corp
```

The script creates the full project structure, initialises git, adds Aurora
as a submodule, and runs `composer install`.

## Project structure

```
client-app/
├── vendor/aurora/              # Aurora core (git submodule, read-only)
├── src/Custom/                 # App\Custom\* — client PHP code
├── templates/Custom/           # Client Twig templates
├── migrations/                 # Client-specific Doctrine migrations
├── config/
│   ├── packages-custom.yaml    # Bundle config (Doctrine, Twig, Mailer…)
│   ├── services-custom.yaml    # Service definitions / decorators
│   └── routes-custom.yaml      # Custom routes
├── bin/console                 # Entry point — delegates to Aurora's Kernel
├── public/index.php            # Web entry point
└── .env                        # Client env overrides
```

Rule of thumb: **the client never edits files under `vendor/aurora/`.** All
customisation happens through the extension points below. Updating Aurora is
then a one-liner (`git submodule update --remote vendor/aurora`).

## How Aurora loads client files

Aurora's `Kernel` automatically detects when it runs as a submodule inside a
`vendor/` directory. When detected, it:

1. Loads `config/packages-custom.yaml` — bundle configuration (Doctrine mappings, etc.)
2. Loads `config/services-custom.yaml` — service definitions
3. Adds `templates/` to Twig's lookup path
4. Loads `config/routes-custom.yaml` — custom routes
5. Exposes `%aurora.client_dir%` as a Symfony parameter pointing to the client root

No manual wiring required — the three config files are the entire contract.

---

## Extension points

### 1. Services

Add classes under `src/Custom/`. Declare the namespace in `config/services-custom.yaml`:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

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
        return '#ff0066';
    }
}
```

### 3. Event listeners

Hook into Aurora's flow via `#[AsEventListener]`:

```php
namespace App\Custom\EventSubscriber;

use App\Module\Ecommerce\Event\OrderCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: OrderCreatedEvent::class)]
final class OrderSubscriber
{
    public function __invoke(OrderCreatedEvent $event): void
    {
        // send confirmation email, notify ERP, etc.
    }
}
```

See `src/*/Event/` in aurora for the full catalog of domain events.

### 4. Routes & controllers

Place controllers under `src/Custom/Controller/` and declare them in
`config/routes-custom.yaml`:

```yaml
custom_controllers:
    resource:
        path: ../src/Custom/Controller/
        namespace: App\Custom\Controller
    type: attribute
```

### 5. Twig templates

The client's `templates/` directory is registered in Twig's path. To override
an Aurora template, mirror its path:

```
# Aurora template
vendor/aurora/templates/Core/admin/layout.html.twig

# Client override
templates/Core/admin/layout.html.twig
```

To add new templates (not overrides), place them anywhere under `templates/`:

```twig
{# templates/Custom/invoice.html.twig #}
{% extends 'Core/admin/layout.html.twig' %}
```

### 6. Custom entities & migrations

Declare the Doctrine mapping and a separate migrations path in
`config/packages-custom.yaml`:

```yaml
doctrine:
    orm:
        mappings:
            AppCustom:
                type: attribute
                is_bundle: false
                dir: '%aurora.client_dir%/src/Custom'
                prefix: 'App\Custom'
                alias: AppCustom

doctrine_migrations:
    migrations_paths:
        'ClientMigrations': '%aurora.client_dir%/migrations'
```

Create the entity in `src/Custom/Entity/`:

```php
namespace App\Custom\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Contract
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;
}
```

Then generate and run the migration:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### 7. Bundle configuration

Any Symfony bundle configuration (Twig globals, rate limiter rules…) goes in
`config/packages-custom.yaml`:

```yaml
twig:
    globals:
        client_name: '%env(APP_NAME)%'
```

### 8. Environment variables

`.env` in the client root is loaded by the runtime. Redefine any Aurora
default here. Common overrides:

- `APP_NAME`, `APP_SECRET`
- `DATABASE_URL`
- `MAILER_DSN`, `MAILER_FROM`, `ADMIN_EMAIL`

Never commit secrets — use `.env.local` (gitignored) for local values.

### 9. Theme & branding

Configure the active theme's `primary_color` in the admin UI — `ThemeContext`
regenerates the full accent palette (50 → 950) from that single seed.

For deeper branding (logo, footer, header text), use the theme config fields
surfaced by `ThemeContext`.

---

## Updating Aurora in a client

```bash
git submodule update --remote vendor/aurora
git add vendor/aurora
git commit -m "chore: bump aurora to <sha>"
```

Breaking changes are listed in Aurora's `CHANGELOG.md` under a `BREAKING:` line.

---

## What is NOT an extension point

The following are internal and may change without notice:

- Private methods of any service
- Internal CSS variable names not prefixed with `--th-*` or `--color-*`
- Database migration internals
- Class names of helpers not under a `*\Contract\` or `*\Api\` namespace
