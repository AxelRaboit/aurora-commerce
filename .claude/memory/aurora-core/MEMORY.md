# Aurora-core — index mémoire projet

Mémoires organisées par domaine. Ouvrir le sous-index du domaine concerné.

**Règle de placement** : PHP serveur → `backend/`. Vue interface admin → `vue-backend/`.
Vue/Twig site public → `vue-frontend/`. S'applique aux deux → `vue-transversal/`.

## Index des domaines

- [backend/MEMORY.md](backend/MEMORY.md) — PHP / Symfony / Doctrine : conventions, structure, pièges
- [vue-backend/MEMORY.md](vue-backend/MEMORY.md) — Vue / JS interface admin : forms, modales, tables, composants
- [vue-frontend/MEMORY.md](vue-frontend/MEMORY.md) — Vue / Twig site public : search, templates, ThemeResolver
- [vue-transversal/MEMORY.md](vue-transversal/MEMORY.md) — Vue / JS transversal : directives, i18n, fetch, assets structure
- [architecture/MEMORY.md](architecture/MEMORY.md) — décisions architecturales et patterns cross-modules
- [process/MEMORY.md](process/MEMORY.md) — méthode de travail, audits, commits
- [preferences/MEMORY.md](preferences/MEMORY.md) — préférences utilisateur et contexte projet

## Règles d'usage

- **Lecture** : ouvrir le sous-index du domaine touché, puis les fichiers pertinents.
  Ne pas se reposer sur le résumé seul — ouvrir le fichier source.
- **Écriture** : créer `<type>_<topic>.md` dans le bon dossier + ligne dans le sous-index.
  Format : `## Règle` → `## Pourquoi` → `## Comment l'appliquer`.
- **Sync** : après tout ajout/modif, lancer `make sync-claude-memory`.
