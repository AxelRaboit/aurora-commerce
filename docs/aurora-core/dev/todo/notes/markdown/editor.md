# Notes — Éditeur Vue

- [ ] **`NotesApp.vue`** (Layer 5) avec `extraFields` + slots `extra-headers/extra-cells/extra-form-fields`. **Variante full-page** (pas modal), comme l'éditeur Post.
- [ ] **Layout** : arbo à gauche (`NoteTree.vue` + `NoteTreeItem.vue`) + éditeur markdown au centre + preview live togglable. Préférence édition/preview persistée.
- [ ] **Composables Vue à porter** depuis `onyx/resources/js/composables/notes/` (réutilisables tels quels, adapter les endpoints) :
  - `markedCallouts.js`, `markedCheckboxes.js`, `markedEmbeds.js`, `markedHighlight.js` — extensions marked.js
  - `useNoteOutline.js` — sommaire (TOC) de la note courante
  - `useNoteShortcuts.js` — raccourcis clavier
  - `useNoteTemplates.js` — templates réutilisables
  - `useNoteTree.js` — construction de l'arbre depuis la liste plate, drag-drop, reorder
  - `useSlashCommands.js` — palette `/` pour insérer blocs (titres, listes, callouts…)
  - `useNoteFilters.js` — recherche full-text + filtres tags côté front
- [ ] **Composables form** : `useNoteEditor.js` (édition) + `useNoteCrud.js` (création depuis l'arbo). Variante 2 documentée (composables séparés, comme User invite/edit).
- [ ] **Vérifier compat** marked.js + version utilisée par Aurora vs Onyx.
- [ ] **Note du jour** : action UI qui ouvre/crée la note `YYYY-MM-DD`.
- [ ] **i18n** : récupérer les clés depuis `onyx/lang/` (fr/en/es/de) → `translations/notes.{locale}.yaml`.
