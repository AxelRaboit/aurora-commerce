# Notes — Wiki-links & graph

## Autocomplete `[[` — ✅ Fait (2026-05-16)

- [x] Composable `useWikiLinkAutocomplete.js` porté depuis Onyx — détecte
      un `[[` non fermé sur la ligne courante, ouvre un dropdown filtré
      sur les titres des notes (cap à 8 suggestions, sous-chaîne case-
      insensitive). Mirror-div positioning pour suivre le curseur.
- [x] Wiring dans `useNoteEditorTextarea` à côté de la slash palette :
      les deux menus sont mutuellement exclusifs (patterns disjoints),
      l'event keydown est consommé par celui qui est ouvert.
- [x] `[[Title]]` inséré sur Enter/Tab/clic, curseur replacé après `]]`.
- [x] Tests vitest : 10 cas (open/close, filtre, navigation clavier,
      pick, fallback untitled, etc.).

## Backend — ✅ Fait (commit `fca34119`)

- [x] **Wiki-links `[[titre]]`** parsing + rendu cliquable en preview. Marked.js extension `markedWikiLinks.js`.
- [x] **Rename automatique** : changement de titre → remplace `[[old]]` par `[[new]]` dans toutes les autres notes user. Logique dans `MarkdownNoteManager::update` + helper protected `renameWikiLinks`.
- [x] **Backlinks** — endpoint `GET /backend/notes/markdown/{id}/backlinks`. Composable `useNoteSidePanel.js` côté front.
- [x] **Unlinked mentions** — endpoint `GET /backend/notes/markdown/{id}/unlinked-mentions`. Mêmes hooks SidePanel.
- [x] **Graph data** — endpoint `GET /backend/notes/markdown/graph` retourne `{nodes, edges}` parsé via regex `[[…]]`.

## Frontend — Graph view : ✅ Fait (2026-05-16)

- [x] **Composant `NoteGraph.vue`** consommant l'endpoint `/graph` via
      `useMarkdownNotesApi.graph()`. Pas de lib externe — port du
      renderer canvas + force-simulation custom d'Onyx
      (`useNoteGraph.js` composable, ~250 LoC). Cytoscape avait été
      tenté en premier mais (a) ~140kb gzip pour dessiner des points,
      (b) le layout `cose` donnait un rendu industriel arrows + cadre
      qui jurait avec l'esthétique Obsidian. Le moteur custom : nœuds
      indigo, taille selon connexions, edges fines low-opacity,
      gravity-center, drag-to-rearrange.
- [x] **UI d'accès** : icône `Network` (lucide) dans le header de la
      sidebar, à côté du `+`. Disponible même sans note sélectionnée.
- [x] **Click sur node** → emit `navigate` → `selectNote(id)` côté
      parent, ferme la modale.
- [x] **Highlight** : la note actuellement ouverte est marquée
      (`.selected` rouge plus gros).
- [x] **Layout** : force-simulation maison sur canvas — répulsion
      pairwise 1/d² (constante 800), edge spring rest-length 100,
      gravité centrale 0.001, damping 0.85, 120 itérations sur open
      puis re-simule au drag. Drag-and-drop d'un nœud rebrasse les
      voisins.
- [ ] **Filtres** (optionnel v1) : par tag, par profondeur de wiki-link
      — pas implémenté, à voir avec un volume réel de notes.

## Perf

- Backend : `backlinks` / `unlinkedMentions` / `graph` chargent tout le
  `content` user en RAM (décryption côté Doctrine Type). Acceptable
  jusqu'à ~qq centaines de notes. À monitorer.
- Frontend graph : Cytoscape peut tenir 1000-2000 nodes facilement avec
  un force layout. Au-delà, switcher sur Sigma.
