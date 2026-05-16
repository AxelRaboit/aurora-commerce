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

### Auto-save ✅ (fait, 2026-05-16)

- [x] Debounced auto-save (800 ms) sur toute modification du titre / contenu / tags.
- [x] Statut visible dans le header (`Modifications non enregistrées`,
      `Enregistrement…`, `Enregistré`, `Échec`) avec icônes `Loader2`/`Check`/etc.
- [x] Flush forcé avant : navigation vers une autre note, ouverture
      d'un wiki-link, suppression de la note, `beforeunload`.
- [x] Bouton "Enregistrer" du header retiré (auto-save remplace la
      sauvegarde manuelle). Le manager backend reste inchangé — il sera
      réutilisable pour un futur bouton de re-publication si besoin.
- [x] Bug corrigé en passant : `isDirty` comparait `form.content` à un
      `selectedNote` sans content (liste plate côté repo), donc toujours
      `true`. Désormais comparé contre un `loadedSnapshot` séparé qui
      stocke la valeur retournée par `api.show(id)`.

### Tags UI ✅ (fait)

- [x] **Input multi-tag** dans le header de l'éditeur (sous la rangée
      titre/actions), via `AppTagsInput` partagé. `isDirty` étendu pour
      détecter une modif de tags.
- [x] **Filtre par tag** dans la sidebar — composable `useNoteTagFilter.js`
      (agrège les tags uniques, gère la sélection), consommé par
      `useNoteTree` qui filtre l'arbo (sémantique OR, ancêtres préservés).
      Pills via `AppTab` size="xs" ; drag-drop désactivé quand le filtre
      est actif.
- [x] **Gestion globale des tags** — modale `NoteTagManagerModal.vue`
      ouverte depuis l'icône engrenage du panneau filtre (`Settings2`).
      Renommer / fusionner (sélection ≥2) / supprimer un tag à travers
      toutes les notes de l'utilisateur. Backend : 4 endpoints
      `/backend/notes/markdown/tags{,/rename,/merge,/delete}` +
      `MarkdownNoteManager::{tagCounts,renameTag,mergeTags,removeTag}`
      avec dédoublonnage automatique et audit log `notes_markdown.tag.*`.
- [ ] **Badge tags** sur chaque rangée de l'arbo (optionnel, peut être
      lourd visuellement) — non implémenté.

### Slash commands ✅ (fait, 2026-05-16)

- [x] Composable `useSlashCommands.js` porté depuis Onyx avec labels
      i18n (`notes.markdown.slash.*`) et détection ligne-débute-par-`/`.
- [x] Palette flottante positionnée via mirror-div (top/left absolus
      relatifs au textarea) — composant dédié `NoteEditor.vue` qui
      remplace `AppTextarea` dans `MarkdownNotesApp.vue` (raccourci
      nécessaire car `AppTextarea` n'expose pas le textarea ref ni les
      événements keydown). 15 commandes : H1-H3, listes, checkbox,
      citation, séparateur, code, callout, lien wiki, gras, italique,
      barré, table.
- [x] Raccourcis clavier : ArrowUp/Down, Enter/Tab pour valider,
      Escape pour fermer. Hover souris met à jour l'index sélectionné.
- [x] Tests vitest : `useSlashCommands.test.js` (9 tests).

### Syntax highlighting code blocks ✅ (fait, 2026-05-16)

- [x] `markedHighlight.js` dans `composables/markedExtensions/` avec
      highlight.js core + 11 langages (js, ts, php, css, html/xml, json,
      bash, sql, python, markdown, yaml). Renderer marked override avec
      wrapper `.code-block` + label langue. Pèse ~138kb gzip dans le
      chunk `NotePreview` (lazy-loaded — pas dans `MarkdownNotesApp`).
- [x] CSS dans `assets/css/modules/notes/markdown/preview.css` :
      wrapper `.code-block` + palette de tokens hljs aurora-tintée
      (purple/sky/emerald/amber/red), thème compatible light+dark via
      `--color-*` variables.
- [x] Couverture vitest étendue (15 tests dans `useMarkdownRenderer.test.js`).

### Recherche tree étendue ✅ (fait, 2026-05-16)

- [x] La barre de recherche dans la sidebar ne fait plus seulement
      "title substring" : elle matche maintenant **title OR any tag OR
      content** (cas-insensitive).
- [x] **Tags** : matchés côté client à partir de la flat list (les
      tags voyagent déjà avec la liste sidebar).
- [x] **Content** : nouveau endpoint `GET /backend/notes/markdown/search?q=…`
      → `MarkdownNoteManager::searchContent` charge les notes
      décryptées et retourne la liste des `id` qui contiennent la
      query. Fetch debouncé (300ms) depuis `useMarkdownNotesPage`,
      résultat mergé dans `useNoteTree` via un `Set<id>` 4ème
      paramètre `contentMatchIdsRef`.
- [x] Composable `useNoteTree` accepte un set d'ids "content-matched"
      en plus du title/tags. La fonction `matchesQuery` factorise les
      3 sources de matching.
- [x] `useMarkdownNotesApi` utilise désormais `HttpMethod` (enum JS
      partagé `@/shared/utils/http/httpMethod.js`) au lieu de strings
      littéraux `"GET"`/`"POST"`.
- [x] +2 tests vitest dans `useNoteTree.test.js` (tag substring +
      content ids). PHPUnit reste vert (58/58 sur les Notes).

### Raccourcis clavier markdown ✅ (fait, 2026-05-16)

- [x] Composable `useMarkdownShortcuts.js` (port d'Onyx). 10 raccourcis
      avec gestion `Ctrl` (Linux/Win) + `Cmd` (Mac) via `event.metaKey`.
- [x] Wrap selection : Ctrl+B (`**…**`), Ctrl+I (`*…*`), Ctrl+E
      (`` `…` ``), Ctrl+K (`[…](url)`), Ctrl+Shift+K (fenced code),
      Ctrl+Shift+X (`~~…~~`).
- [x] Line prepend : Ctrl+H (`# `), Ctrl+L (`- `), Ctrl+Shift+L (`1. `),
      Ctrl+Shift+C (`- [ ] `).
- [x] `preventDefault()` sur match → bloque les handlers natifs
      (Firefox Ctrl+K = focus barre d'URL, etc.).
- [x] Branché en priorité dans `useNoteEditorTextarea.onKeydown`, avant
      les popovers slash/wiki.
- [x] Tests vitest : 14 cas (chaque raccourci + Cmd vs Ctrl).

### Responsive mobile ⏳ À faire

L'éditeur est aujourd'hui conçu desktop : layout flex 3 colonnes
(sidebar 288px + édition splittable + preview), popovers slash/wiki
positionnés via mirror-div sans clamp viewport, modale graphe à
`max-w-6xl × 80vh`. Sur écran étroit (< ~768px) tout déborde ou
s'écrase.

Chantiers à prévoir :
- [ ] **Sidebar** → drawer escamotable derrière un bouton burger
      (overlay translucide quand ouverte). Ferme sur sélection d'une
      note pour libérer la vue éditeur.
- [ ] **Layout éditeur** : forcer `viewMode='edit'` ou `'preview'`
      sur mobile (le split 50/50 n'a pas de sens en portrait), avec
      un toggle pour basculer.
- [ ] **Popovers slash + wiki** : clamp horizontal pour qu'ils ne
      sortent pas du viewport (recalculer `left` après le mirror-div
      avec `Math.min(left, window.innerWidth - menuWidth - 8)`).
      Idéalement aussi un bottom-clamp pour pousser le menu au-dessus
      du curseur quand il déborderait en bas.
- [ ] **Modale graphe** : passer en plein écran sur mobile
      (`max-w-full max-h-screen` au lieu de `max-w-6xl × 80vh`).
      Le canvas custom respecte déjà DPR, juste à adapter la taille.
- [ ] **Tags input + filtre** : actuellement deux rangées dans la
      sidebar — empilage vertical OK, vérifier que les pills tags
      wrappent correctement.
- [ ] **Side panel backlinks/mentions** : sur mobile l'ouvrir en
      plein écran plutôt qu'en panneau collé à droite.

Aucun travail backend nécessaire — c'est purement Tailwind/CSS +
quelques refactors dans `MarkdownNotesApp.vue` autour des
breakpoints `md:` / `lg:`.

### À considérer plus tard (Onyx avait, on a pas encore)

- [ ] **Note du jour** — action UI qui ouvre/crée la note `YYYY-MM-DD`.
- [ ] **Templates** réutilisables (composable `useNoteTemplates.js`).
- [ ] **Outline / TOC** de la note courante (composable `useNoteOutline.js`).
- [ ] **i18n** : récupérer les clés depuis `onyx/lang/` (fr/en/es/de) —
      les nôtres en fr+en suffisent pour le MVP.
