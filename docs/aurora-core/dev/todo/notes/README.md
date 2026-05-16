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
| Markdown | 🟢 **Terminé** — production-ready, plus rien à faire côté core. Voir le code dans `src/Module/Notes/Markdown/` et `assets/Module/Notes/backend/markdown/`. |
| Block    | ⏳ **Pas commencé** — spec dans [`block/overview.md`](block/overview.md). Hérite de l'infra Markdown (encryption, images, tree/drag-drop, voter ownership). |

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
