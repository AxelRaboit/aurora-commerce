# Aurora CSS — organisation & conventions

CSS organisé pour **refléter la structure de `src/`** (Vue/JS et PHP
co-localisés depuis 0.5). Chaque
fichier matche la couche/le module qu'il style, pour qu'on retrouve les
styles à côté de leur code logique.

## Structure

```
src/Core/Frontend/css/
├── app.css                    # Entry — orchestre les imports GLOBAUX uniquement
├── email.css                  # Standalone, mounted by emails only
│
├── base/                      # Tailwind base + theme tokens (globaux)
│   ├── base.css
│   ├── scrollbar.css
│   ├── theme.css
│   └── theme-transition.css
│
├── shared/                    # Styles pour les composants de src/Core/Frontend/shared/
│   ├── input.css              #   (importés dans app.css — utilisés partout)
│   ├── loader.css
│   └── modal.css
│
├── core/                      # Styles pour src/Core/Frontend/*
│   └── sidemenu.css           #   (importé par AppSidemenu.vue)
│
└── modules/                   # Styles per-module (mirror de src/Module/<Name>/assets/)
    ├── editorial/
    │   ├── editor.css         #   (importés par EditorBlock.vue)
    │   ├── blocks.css         #
    │   └── prose.css          #   (importé par MergeBlockEntry.vue + RevisionsOverlay.vue)
    └── notes/                 # Module avec sous-modules → un sous-dossier par sub-module
        ├── markdown/
        │   └── preview.css    # (importé par NotePreview.vue)
        └── block/             # (à venir — sub-module Block / EditorJS)
```

> **Module avec sous-modules** : si `src/Module/<Name>/assets/` est lui-même
> compartimenté en sous-modules (cf. `Module/Notes/Markdown/` +
> `Module/Notes/Block/`), reproduire la subdivision côté CSS :
> `modules/<name>/<submodule>/<feature>.css`. Le nom de fichier peut
> raccourcir puisque le dossier parent disambiguate (`preview.css`
> plutôt que `markdown-preview.css`).

## Règle d'or — où importer ?

Vite/Rolldown trackent les `import "...css"` par chunk JS : **le CSS est
shipé dans le même chunk que la JS qui le consomme**. Donc :

- **Importer dans `app.css`** seulement si le CSS est **vraiment**
  utilisé sur toutes (ou presque) les pages : base, theme, scrollbar,
  composants shared utilisés partout (input, modal, loader).
- **Importer dans le SFC** dès qu'un CSS est lié à une feature/module
  précis : EditorJS host, sidebar admin, preview markdown, etc. Le
  navigateur ne télécharge le CSS que quand la page qui mount le SFC
  est chargée.

### Exemple — NotePreview

```vue
<script setup>
import '@/css/modules/notes/markdown/preview.css';  // ⬅ CSS d'abord, séparée d'une ligne vide

import { computed } from 'vue';
import { useMarkdownRenderer } from '@notes/backend/markdown/composables/useMarkdownRenderer.js';
</script>
```

Si tu visites `/backend/dashboard`, `preview.css` n'est jamais
téléchargé. Tu vas sur `/backend/notes/markdown`, il arrive avec le
chunk `MarkdownNotesApp.js`.

### Ordre des imports dans un `.vue`

1. **CSS d'abord** (`import "@/css/..."`), un par ligne.
2. **Ligne vide** comme séparateur.
3. **JS imports ensuite** (Vue, composables, components, utils).

Pourquoi : ça met les side-effects (le CSS est un side-effect import) en
tête de fichier, et ça matche l'ordre dans lequel le navigateur applique
les styles avant le rendu JS.

## Conventions

### 1. Où vivent les fichiers

| Type de style | Emplacement | Importé depuis |
|---|---|---|
| **Base / theme** (tokens, scrollbar, body) | `base/` | `app.css` |
| **Composant partagé global** (`src/Core/Frontend/shared/`) | `shared/` | `app.css` |
| **Core admin** (`src/Core/Frontend/*`) | `core/` | le SFC concerné |
| **Module** (`src/Module/<Name>/assets/*`) | `modules/<name>/` | le SFC concerné |

### 2. Inline `<style>` vs fichier externe vs Tailwind

**Préférer Tailwind via `:class`** pour 95% des cas (le design system y est).

**Préférer un fichier externe** dès qu'on a :
- Plus de ~5 règles cohérentes
- Du CSS qui cible du contenu **rendu** (`v-html`, EditorJS, marked, …) —
  les styles scopés Vue ne peuvent pas styler du HTML injecté sans
  `:deep()` partout, et le résultat est plus net dans un `.css` dédié.
- Des règles qui dépendent d'une classe parente fixée par le composant
  (`.note-preview`, `.prose-preview`, `.codex-editor`, …) — la portée
  est déjà naturelle, on peut sortir les rules d'un coup.

**Garder inline `<style scoped>`** uniquement pour :
- Quelques règles très locales à un SFC sans dépendance externe.
- Des animations / keyframes spécifiques au composant.

### 3. Naming d'un fichier de module

- 1 fichier par feature/preview cohérent : `markdown-preview.css`,
  `editor.css`, `blocks.css`, …
- Pas de fourre-tout `notes.css` qui mixerait plusieurs features.
  Préférer plusieurs petits fichiers.
- Toutes les règles **doivent être scopées** par une classe racine
  spécifique au module (`.note-preview`, `.editor-block-holder`,
  `.prose-preview`, …) pour éviter de polluer globalement.

### 4. Ajouter un nouveau fichier

1. Créer le fichier dans le bon dossier (`modules/<name>/<feature>.css`).
2. **L'importer depuis le SFC qui en a besoin** (`import "@/css/...";`
   en haut du `<script setup>`). Ne **pas** l'ajouter à `app.css` sauf
   si vraiment global.
3. Header comment expliquant **quel composant / feature** consomme ces
   styles, et **où** la classe racine est posée (cf.
   `markdown-preview.css` pour le template).

### 5. Variables CSS / tokens de couleur

Les tokens (`--th-primary`, `--th-surface`, `--color-accent-500`, …) sont
définis dans `base/theme.css`. **Toujours** passer par les tokens — pas
de hex en dur dans les feuilles modulaires, sauf pour des accents
type-spécifiques (cf. les `--callout-color` de markdown-preview).

### 6. `email.css`

Standalone — chargé indépendamment par `templates/Shared/email/layout/
base.html.twig` via `inline_css()`. **Ne pas** l'importer dans
`app.css` ni inclure des selectors Tailwind compliqués (les clients
mail ne supportent que CSS inline limité).
