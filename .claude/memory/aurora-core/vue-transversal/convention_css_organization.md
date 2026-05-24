---
name: convention_css_organization
description: Organisation du CSS Aurora — où vivent les fichiers (base/shared/core/modules) et règle d'import (app.css uniquement pour le global, SFC sinon)
metadata:
  type: feedback
---

## Règle

CSS organisé pour **mirror la structure `src/`** (co-localisée avec le
PHP depuis 0.5) : `src/Core/assets/css/{base,shared,core,modules}/`.
CSS spécifique à un SFC vit à côté du SFC sous
`src/Module/<Name>/assets/`. Documentation complète :
[`docs/aurora-core/dev/css_conventions.md`](../../../../docs/aurora-core/dev/css_conventions.md).

### Où importer ?

| Type de style | Emplacement | Importé depuis |
|---|---|---|
| Base / theme (tokens, scrollbar, body) | `base/` | `app.css` |
| Composant shared global (input, modal, loader) | `shared/` | `app.css` |
| Core admin (sidemenu, …) | `core/` | le SFC concerné |
| Module (`src/Module/<Name>/assets/*`) | `modules/<name>/` | le SFC concerné |

**Critère** : importer dans `app.css` uniquement si vraiment global
(quasi toutes les pages). Sinon importer dans le SFC qui consomme — Vite
émet alors le CSS dans le même chunk JS que le composant, code-splitting
automatique.

### Ordre des imports dans un `.vue`

```vue
<script setup>
import "@/css/modules/notes/markdown-preview.css";  // 1. CSS d'abord

import { computed } from "vue";                      // 2. ligne vide, puis JS
import { useMarkdownRenderer } from "@notes/...";
</script>
```

CSS d'abord (side-effect import) → ligne vide → JS ensuite (composables,
components, utils). Matche l'ordre d'application navigateur (styles
avant rendu JS).

### Inline `<style scoped>` vs fichier externe

- **Tailwind via `:class`** pour 95% des cas.
- **Fichier externe** (`modules/<name>/<feature>.css`) dès qu'on style
  du contenu rendu (`v-html`, EditorJS, marked) ou qu'on dépasse ~5
  règles cohérentes — les `:deep()` partout dans `<style scoped>` sont
  un anti-pattern.
- **Inline `<style scoped>`** uniquement pour : 1-2 règles très locales,
  animations/keyframes propres au composant.

## Pourquoi

- Retrouver les styles à côté de leur code (même org que les SFC).
- Code-splitting CSS automatique via les imports per-SFC : un user qui
  ne visite pas `/notes/markdown` ne télécharge jamais
  `markdown-preview.css`.
- Évite les `:deep()` agressifs pour styler du contenu injecté
  (marked/EditorJS/v-html).

## Comment l'appliquer

1. Nouveau CSS pour une feature → créer
   `src/Core/assets/css/modules/<module>/<feature>.css`.
2. L'importer **dans le `<script setup>` du SFC** qui le consomme, en
   tête, séparé du JS par une ligne vide.
3. Si vraiment global (réutilisé partout) → ajouter à `app.css`.
4. Header comment dans le fichier CSS qui explique quel composant +
   quelle classe racine pose le scope.

### Module avec sous-modules

Si le module est compartimenté en sub-modules côté `src/` (cf.
`Module/Notes/Markdown/` + `Module/Notes/Block/`), reproduire côté CSS :
`modules/<name>/<submodule>/<feature>.css`. Le dossier parent
disambiguate → nom de fichier court (`preview.css` plutôt que
`markdown-preview.css`).

### Modules déjà conformes (référence)

| Module / Section | Fichier(s) CSS | Importé par |
|---|---|---|
| Core sidemenu | `core/sidemenu.css` | `AppSidemenu.vue` |
| Editorial editor | `modules/editorial/editor.css`, `blocks.css` | `EditorBlock.vue` |
| Editorial prose | `modules/editorial/prose.css` | `MergeBlockEntry.vue`, `RevisionsOverlay.vue` |
| Notes / Markdown preview | `modules/notes/markdown/preview.css` | `NotePreview.vue` |
