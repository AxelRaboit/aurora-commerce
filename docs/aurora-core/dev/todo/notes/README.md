# Notes — sous-modules

Le module `src/Module/Notes/` regroupe deux sous-modules de prise de
notes, partageant l'infra (arborescence, tags, recherche, images,
chiffrement, ownership) mais avec deux techniques de stockage et deux
UX distinctes :

- **Markdown** — éditeur texte markdown + wiki-links + graph (port d'Onyx)
- **Block** — éditeur block-based (JSON typé) via EditorJS

## État global (au 2026-05-16)

| Sous-module | Statut |
|---|---|
| Markdown | 🟢 **MVP utilisable** — backend complet + UI Vue (CRUD + preview + wiki-links + drag-drop + side panel backlinks/mentions + tags UI/filtre + slash commands + graphe Cytoscape + syntax highlighting hljs) + demo fixtures. Reste : images, import Onyx. |
| Block | ⏳ Pas commencé — spec dans [`block/overview.md`](block/overview.md) |

## Sous-modules

### [`markdown/`](markdown/) — Notes Markdown

| Couche / chantier | Statut | Doc |
|---|---|---|
| Entity + DTO + Repository + migration | ✅ Fait | [entity.md](markdown/entity.md) |
| Manager + Serializer + Controller | ✅ Fait | [manager.md](markdown/manager.md) |
| Chiffrement at-rest (Doctrine Type Encryption) | ✅ Fait | [encryption.md](markdown/encryption.md) |
| Wiki-links (rename auto, backlinks, mentions, graph) — **backend** | ✅ Fait | [wiki-links.md](markdown/wiki-links.md) |
| Éditeur Vue — squelette CRUD + tree + drag-drop + preview live + side panel | ✅ Fait | [editor.md](markdown/editor.md) |
| Éditeur Vue — **tags UI** (input + filtre sidebar) | ✅ Fait | [editor.md](markdown/editor.md) |
| Éditeur Vue — **slash commands** (palette `/`) | ✅ Fait | [editor.md](markdown/editor.md) |
| Vue graphe des wiki-links (frontend, Cytoscape) | ✅ Fait | [wiki-links.md](markdown/wiki-links.md) |
| Syntax highlighting code blocks (highlight.js) | ✅ Fait | [editor.md](markdown/editor.md) |
| Images (upload + serve + cleanup) | ⏳ À faire | [images.md](markdown/images.md) |
| Script d'import depuis Onyx | ⏳ À faire | [import.md](markdown/import.md) |

### [`block/`](block/) — Notes Block (EditorJS)

⏳ Pas commencé. Spec dans [`block/overview.md`](block/overview.md). À
démarrer **après** stabilisation complète de Markdown.

## Prochaine session — ordre suggéré

Par ROI décroissant :

1. **Images** — drag-drop upload + endpoint serve + cleanup orphelines
   au save/delete. Plus lourd (storage + sécurité) mais feature
   bloquante pour usage réel.
2. **Import Onyx** — commande Symfony one-shot, à faire quand on aura
   du contenu Onyx à migrer.

## Architecture commune

- **Entités séparées** `MarkdownNote` et `BlockNote` (pas de
  discriminator) — voir justifications dans
  [`block/overview.md`](block/overview.md). Si plus tard une vue unifiée
  "toutes mes notes" devient utile, extraire les champs communs
  (`tags`, `user`, `parent`, `position`) dans une abstract partagée.
- **Réutilisations directes** côté Block depuis Markdown :
  - Type Doctrine encrypted (`markdown/encryption.md`) — déjà en place
  - Controller images (`markdown/images.md`) — futur partagé
  - Pattern Manager 5-couches + voter ownership — modèle prêt
  - Composables `useNoteTree.js`, `useNoteDragDrop.js` (drag-drop arbo) — réutilisables tels quels
  - i18n base
- **Ordre d'exécution** : démarrer par Markdown (en cours). Block vient
  ensuite et hérite de l'infra.
