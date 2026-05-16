# Aurora — Convention de naming (fichiers, dossiers, identifiants)

> **Statut** : adoptée 2026-05-16. S'applique à aurora-core ET aurora-client.
> Mémoire shared : `convention_naming.md`.

## Heuristique mnémotechnique

> **Lu par un humain dans une URL ou dans le filesystem** → `kebab-case`
> **Identifiant interne** (route, setting, colonne DB, traduction) → `snake_case`
> **Classe PHP / composant Vue** → `PascalCase`
> **Variable / fonction JS** → `camelCase`

## Tableau complet

| Type | Format | Exemple |
|---|---|---|
| **PHP class / namespace** | `PascalCase` | `MarkdownNoteManager`, `Aurora\Module\Notes\Markdown\Service` |
| **PHP file (filename)** | `PascalCase.php` (= classe) | `MarkdownNoteManager.php` |
| **Vue component** | `PascalCase.vue` | `MarkdownNotesApp.vue`, `NoteTreeItem.vue` |
| **JS composable / util** | `camelCase.js` | `useNoteImageUpload.js`, `markedImageDimensions.js` |
| **JS test file** | `camelCase.test.js` | `useNoteTree.test.js` |
| **CSS class** | `kebab-case` | `.note-image-wrap`, `.task-list-item` |
| **CSS file** | `kebab-case.css` | `preview.css`, `image-dimensions.css` |
| **Folder dans `assets/`** | `kebab-case` | `assets/Module/Ged/backend/document-categories/` |
| **Folder dans `templates/`** | `kebab-case` | `templates/Module/Ged/backend/document-categories/` |
| **Twig file** | `kebab-case.html.twig` ou `snake_case.html.twig` | (conventionnellement snake_case côté `Shared/components/`, kebab pour les nouvelles pages) |
| **URL path** (Symfony route path) | `kebab-case` | `/backend/access-request`, `/uploads/media/...` |
| **Symfony route name** | `snake_case` | `backend_media_upload`, `uploads_serve` |
| **Setting / i18n / DB column key** | `snake_case` | `notes_markdown_image_max_edge`, `posts_per_page` |
| **Doctrine entity table** | `snake_case` (`<plural>` ou `core_<plural>`) | `markdown_notes`, `core_markdown_notes` |
| **Doctrine sequence** | `snake_case` (`seq_core_<entity>_id`) | `seq_core_markdown_note_id` |
| **Doc folder** | `kebab-case` | `getting-started/`, `entity-extensibility/` |
| **Doc file** | `kebab-case.md` ou `snake_case.md` (les deux acceptés historiquement) | `entity_extensibility_convention.md`, `storage_policy.md` |
| **Service ID Symfony** | `kebab-case` ou `snake_case` (généralement le FQCN) | `app.upload_dir`, `Aurora\Core\Storage\BinaryFileServer` |
| **Twig namespace** | `@PascalCase` (= module) | `@Notes/backend/markdown/index.html.twig` |
| **i18n namespace key** | `snake_case` segmenté par `.` | `notes.markdown.image.upload_failed` |

## Cas limite : noms composés

- Notes Markdown = **deux mots**
  - Folder asset : `assets/Module/Notes/backend/markdown/` (1 mot, OK)
  - URL : `/backend/notes/markdown/...` (chacun mot, séparé par `/` — OK)
  - Setting : `notes_markdown_image_max_edge` (snake)
  - Route : `backend_notes_markdown_image_upload` (snake)
  - CSS : `.note-image-wrap` (kebab)

- Listing Categories = **deux mots**
  - Folder asset : `assets/Module/Ecommerce/backend/listing-categories/` ✅
  - URL : `/backend/listing-categories/...`
  - Setting : `ecommerce_listing_categories_per_page`
  - Route : `backend_ecommerce_listing_categories_list`

## Anti-patterns

❌ `assets/Module/Crm/backend/contact_tags/` → ✅ `contact-tags/`
❌ Mixer kebab et snake dans un même type (ex : la moitié des folders en kebab et l'autre en snake)
❌ `getProfilePhotoUrl()` retournant `/uploads/users_profile_photos/...` (URL doit être kebab)
❌ Setting key avec dash : `'notes-markdown-image'` (les conventions YAML/PHP enum sont snake)

## Mécanisme de contrôle

- Ajouter à PHPStan ou à un pre-commit une règle qui scanne les noms de dossiers/fichiers nouveaux contre la convention.
- Auditer périodiquement via :
  ```bash
  find assets templates -type d -name "*_*" | grep -v node_modules
  find docs -type d -name "*_*"
  ```

## Why

- **Cohérence cross-module** : un dev qui ouvre `Ecommerce/` doit y voir le même style de naming que `Ged/` ou `Editorial/`.
- **Outillage** : grep / IDE fuzzy find / autocomplétion fonctionnent mieux quand la casse est prédictible.
- **URLs publiques** : kebab-case est la convention web universelle (Stripe, GitHub, MDN, etc.).
- **Identifiants internes** : snake_case est la convention SQL et la plus commune en PHP / YAML.

## Voir aussi

- CLAUDE.md §4 "Conventions de naming" (rappel terse)
- `.claude/memory/aurora-shared/convention_naming.md` (mémoire transversale)
