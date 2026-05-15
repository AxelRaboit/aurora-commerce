# Notes — Éditeur Vue

## ✅ Fait

Commits `46c6e59d` (scaffold UI) + `48131a9a` (live preview) + `f03548a8` (side panel) + `924c0f4b` (search + splitter) + `d72c35f3` (refacto god component) + `dd59a775` puis `0342e6bf` (drag-drop natif HTML5).

- [x] **`MarkdownNotesApp.vue`** (Layer 5) — variante full-page.
- [x] **Layout** : arbo à gauche (récursif via `NoteTreeItem.vue`) + éditeur markdown au centre + preview live togglable. Mode édition/split/preview persisté en localStorage.
- [x] **Splitter resizable** entre éditeur et preview (composable partagé `useResizable`).
- [x] **Recherche** dans l'arbo (filtre titre case-insensitive, ancêtres préservés).
- [x] **Side panel** backlinks + unlinked mentions (composable `useNoteSidePanel`).
- [x] **Drag-drop hiérarchique** natif HTML5 (composable `useNoteDragDrop`, pattern MediaApp).
- [x] **Modale de suppression** (au lieu de `window.confirm`).
- [x] **Composables Vue portés depuis Onyx** :
  - [x] `markedCallouts.js` (extensions Obsidian-style callouts)
  - [x] `markedCheckboxes.js` (task list interactive)
  - [x] `markedWikiLinks.js` (`[[title]]` parsing + fallback)
  - [x] `useMarkdownRenderer.js` (orchestre marked + DOMPurify + extensions)
  - [x] `usePreviewClickRouter.js` (event delegation v-html → emit)
  - [x] `useNoteTree.js` (build hierarchy depuis flat list, filtre)
  - [x] `useNotesEditor.js` (CRUD + dirty + wiki-link nav + checkbox auto-save + beforeunload)
  - [x] `useMarkdownNotesApi.js` (HTTP layer)
  - [x] `useViewMode.js` (persistance localStorage)
- [x] **Composants** : `NoteTreeItem` (avec drag-drop natif), `NotePreview`, `NoteSidePanel`.
- [x] **Tests vitest** : `useMarkdownRenderer.test.js` (13 tests) + `useNoteTree.test.js`.

## ⏳ À faire — chantiers prioritaires

### Tags UI (petit effort, gain UX immédiat)

- [ ] **Input multi-tag** dans le header de l'éditeur (à côté du titre).
      Backend `tags` (json array) déjà en place côté `MarkdownNote`.
      Utiliser `AppTagsInput` partagé si dispo, sinon un wrapper simple.
- [ ] **Filtre par tag** dans la sidebar — liste de tags uniques agrégés
      depuis `notes`, click = filtre les notes affichées dans l'arbo.
      Composable `useNoteTagFilter` ou intégré à `useNoteTree`.
- [ ] **Badge tags** sur chaque rangée de l'arbo (optionnel, peut être
      lourd visuellement).

### Slash commands (effort moyen)

- [ ] Composable `useSlashCommands.js` à porter depuis
      `onyx/resources/js/composables/notes/useSlashCommands.js`.
- [ ] Palette `/` à la position du curseur dans le textarea, insert
      blocs prédéfinis : titre H1-H3, liste à puces, liste numérotée,
      checklist, callout, code block, séparateur.
- [ ] Raccourcis clavier : ArrowUp/Down pour naviguer, Enter pour valider,
      Escape pour fermer.

### Syntax highlighting code blocks (petit effort, niche)

- [ ] Port `markedHighlight.js` depuis Onyx + bundle highlight.js core
      avec langages communs (js, ts, php, py, css, html, json, bash, sql,
      yaml). Peser ~50kb gzip dans le chunk `MarkdownNotesApp.js`.
- [ ] CSS dans `assets/css/modules/notes/markdown/preview.css` (bloc
      `.hljs` + tokens).

### À considérer plus tard (Onyx avait, on a pas encore)

- [ ] **Note du jour** — action UI qui ouvre/crée la note `YYYY-MM-DD`.
- [ ] **Templates** réutilisables (composable `useNoteTemplates.js`).
- [ ] **Outline / TOC** de la note courante (composable `useNoteOutline.js`).
- [ ] **Raccourcis clavier** globaux (composable `useNoteShortcuts.js`).
- [ ] **i18n** : récupérer les clés depuis `onyx/lang/` (fr/en/es/de) —
      les nôtres en fr+en suffisent pour le MVP.
