# Notes — Wiki-links & graph

## Backend — ✅ Fait (commit `fca34119`)

- [x] **Wiki-links `[[titre]]`** parsing + rendu cliquable en preview. Marked.js extension `markedWikiLinks.js`.
- [x] **Rename automatique** : changement de titre → remplace `[[old]]` par `[[new]]` dans toutes les autres notes user. Logique dans `MarkdownNoteManager::update` + helper protected `renameWikiLinks`.
- [x] **Backlinks** — endpoint `GET /backend/notes/markdown/{id}/backlinks`. Composable `useNoteSidePanel.js` côté front.
- [x] **Unlinked mentions** — endpoint `GET /backend/notes/markdown/{id}/unlinked-mentions`. Mêmes hooks SidePanel.
- [x] **Graph data** — endpoint `GET /backend/notes/markdown/graph` retourne `{nodes, edges}` parsé via regex `[[…]]`.

## Frontend — Graph view : ⏳ À faire

- [ ] **Composant `NoteGraph.vue`** consommant l'endpoint `/graph`. Lib à
      choisir :
  - **D3** — flexible, courbe d'apprentissage. ~80kb gzip.
  - **Cytoscape** — riche, plus orienté graph theory. ~120kb gzip.
  - **Sigma** — perf sur gros graphes. ~50kb gzip.
  - **vis-network** — simple drop-in, force layout intégré. ~140kb gzip.
  - **Recommandation initiale** : Cytoscape (mature, beaucoup d'exemples,
    force layout + zoom/pan natifs, intégration Vue facile via wrapper).
- [ ] **UI d'accès** : bouton "Graph" dans le header de l'éditeur ou
      tab dédié dans la sidebar.
- [ ] **Click sur node** → ouvre la note correspondante.
- [ ] **Filtres** (optionnel v1) : par tag, par profondeur de wiki-link.

## Perf

- Backend : `backlinks` / `unlinkedMentions` / `graph` chargent tout le
  `content` user en RAM (décryption côté Doctrine Type). Acceptable
  jusqu'à ~qq centaines de notes. À monitorer.
- Frontend graph : Cytoscape peut tenir 1000-2000 nodes facilement avec
  un force layout. Au-delà, switcher sur Sigma.
