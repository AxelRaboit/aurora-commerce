# Notes — Wiki-links & graph

- [ ] **Wiki-links `[[titre]]`** dans le contenu markdown. Composable `useNoteWikiLinks.js` à porter (parsing + rendu cliquable en preview).
- [ ] **Rename automatique** : quand un titre de note change, remplacer `[[old]]` → `[[new]]` dans toutes les autres notes de l'utilisateur. Logique côté `NoteManager::update` (cf. `NoteService::renameWikiLinks` dans Onyx).
- [ ] **Backlinks** — endpoint `GET /notes/{id}/backlinks` : retourne les notes qui contiennent `[[titre-courant]]`. Composable `useNoteBacklinks.js`.
- [ ] **Unlinked mentions** — endpoint `GET /notes/{id}/unlinked-mentions` : notes qui mentionnent le titre **sans** la syntaxe `[[…]]`.
- [ ] **Graph view** — endpoint `GET /notes/graph` retourne `{nodes, edges}` (nodes = notes, edges = wiki-links). Composant `NoteGraph.vue` à porter.
  - **À identifier** : quelle lib graph Onyx utilise (D3 / Cytoscape / Sigma / vis-network) — inspecter `onyx/resources/js/components/notes/NoteGraph.vue` avant portage.
- [ ] **Perf** : `backlinks`/`unlinkedMentions`/`graph` chargent tout le `content` user en RAM. Acceptable au démarrage, monitorer sur gros volumes.
