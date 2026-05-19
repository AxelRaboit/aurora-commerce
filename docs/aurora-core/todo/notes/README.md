# Notes — sous-modules

Le module `src/Module/Notes/` regroupe deux sous-modules de prise de
notes, partageant l'infra (arborescence, tags, recherche, images,
chiffrement, ownership) mais avec deux techniques de stockage et deux
UX distinctes :

- **Markdown** — éditeur texte markdown + wiki-links + graph
- **Block** — éditeur block-based (JSON typé) via EditorJS

## État

| Sous-module | Statut |
|---|---|
| Markdown | 🟢 **Terminé** — production-ready. Voir `src/Module/Notes/Markdown/` et `src/Module/Notes/assets/backend/markdown/`. |
| Block    | 🟢 **Terminé** — implémentation complète (Entity + Manager + Serializer + Controller + assets EditorJS). Voir `src/Module/Notes/Block/` et `src/Module/Notes/assets/backend/block/`. |

> **Statut global module Notes** : 🟢 Core, plus rien à faire ici. Ce dossier
> reste utile comme **doc historique d'architecture** (justifications des
> choix entités séparées, partage encryption/images/tree, etc.).

## Architecture commune (référence pour le sous-module Block)

- **Entités séparées** `MarkdownNote` / `BlockNote` (pas de
  discriminator) — justification dans [`block/overview.md`](block/overview.md).
  Si plus tard une vue unifiée "toutes mes notes" devient utile,
  extraire les champs communs (`tags`, `user`, `parent`, `position`)
  dans une abstract partagée.
- **Réutilisations directes** côté Block depuis Markdown :
  - Type Doctrine encrypted (déjà en place dans `src/Core/Encryption/`)
  - Controller images (`MarkdownNotesImagesController` + `MarkdownNoteImageService`) — futur partagé
  - Pattern Manager 5-couches + hook `cleanupOrphanedImages` — modèle prêt
  - Composables `useNoteTree.js`, `useNoteDragDrop.js` — réutilisables tels quels
  - i18n base
- **Convention extensibilité** : la variante "tree-based editor" est
  documentée dans `docs/aurora-core/dev/entity_extensibility_convention.md` §4.bis.3.
