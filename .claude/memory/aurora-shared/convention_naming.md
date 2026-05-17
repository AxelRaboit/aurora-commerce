---
name: convention-naming
description: Filesystem + identifier naming across the Aurora ecosystem. kebab-case for human-facing names (URLs, asset folders, CSS), snake_case for internal keys (routes, settings, DB, i18n), PascalCase for classes/components, camelCase for JS values.
metadata:
  type: feedback
---

**Règle d'or** (heuristique mnémotechnique) :

> **Lu par un humain dans une URL ou dans le filesystem** → `kebab-case`
> **Identifiant interne** (route, setting, colonne DB, traduction) → `snake_case`
> **Classe PHP / composant Vue** → `PascalCase`
> **Variable / fonction JS** → `camelCase`

## Application par type

| Type | Format | Exemple |
|---|---|---|
| PHP class / namespace | `PascalCase` | `MarkdownNoteManager`, `Aurora\Module\Notes\Markdown\Service` |
| Vue component | `PascalCase.vue` | `MarkdownNotesApp.vue` |
| JS composable / util | `camelCase.js` | `useNoteImageUpload.js` |
| CSS class | `kebab-case` | `.note-image-wrap` |
| Folder dans `assets/` ou `templates/` | `kebab-case` | `Module/Ecommerce/backend/listing-categories/` |
| URL path | `kebab-case` | `/backend/access-request`, `/uploads/media/...` |
| Symfony route name | `snake_case` | `backend_media_upload`, `uploads_serve` |
| Setting / i18n / DB key | `snake_case` | `notes_markdown_image_max_edge` |
| Doctrine table / sequence | `snake_case` (`seq_core_<entity>_id`) | `seq_core_markdown_note_id` |
| Doc folder | `kebab-case` | `getting-started/`, `entity-extensibility/` |

## Anti-patterns

❌ `assets/Module/Crm/backend/contact_tags/` → ✅ `contact-tags/`
❌ `docs/aurora-client/getting_started/` → ✅ `getting-started/`
❌ Mixer kebab et snake dans un même type sur des modules différents
❌ URL avec underscore (`/forgot_password`) au lieu de dash (`/forgot-password`)
❌ Setting key avec dash au lieu d'underscore

## Doc canonique

Cette mémoire **est** la doc canonique (le fichier `docs/.../naming_convention.md`
de l'ancien temps a été supprimé pour éviter le double-emploi avec CLAUDE.md §4).

## Why

- **Cohérence cross-module** : un dev qui ouvre `Ecommerce/` doit voir
  le même style que `Ged/` ou `Editorial/`.
- **URLs publiques** : `kebab-case` est la convention web universelle
  (Stripe, GitHub, MDN…).
- **Identifiants internes** : `snake_case` est la convention SQL +
  PHP / YAML / settings.
- **Outillage** : grep / IDE fuzzy find / autocomplétion plus
  prévisibles quand la casse est uniforme.

S'applique à **aurora-core ET aurora-client** — quand un client ajoute
un module / un dossier asset / un setting, il suit la même grille.

Lié à [[pref_think_long_term]] (la cohérence cross-écosystème fait
partie de la philosophie "penser long terme").
