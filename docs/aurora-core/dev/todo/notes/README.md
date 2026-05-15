# Notes — sous-modules

Le module `src/Module/Notes/` regroupe deux sous-modules de prise de
notes, partageant l'infra (arborescence, tags, recherche, images,
chiffrement, ownership) mais avec deux techniques de stockage et deux
UX distinctes :

- **Markdown** — éditeur texte markdown + wiki-links + graph
- **Block** — éditeur block-based (JSON typé) via EditorJS

## Sous-modules

### [`markdown/`](markdown/) — Notes Markdown (port d'Onyx)

Éditeur Markdown complet : wiki-links `[[…]]`, graph view, callouts,
slash commands, templates, images drag-drop. Source à porter :
`/home/axel/Documents/dev/personal/onyx/`.

- [Entity & schéma](markdown/entity.md)
- [Manager & Serializer](markdown/manager.md)
- [Éditeur Vue](markdown/editor.md)
- [Wiki-links & graph](markdown/wiki-links.md)
- [Images](markdown/images.md)
- [Chiffrement at-rest](markdown/encryption.md)
- [Script d'import depuis Onyx](markdown/import.md)

### [`block/`](block/) — Notes Block (EditorJS)

Éditeur block-based via [EditorJS](https://editorjs.io) : chaque élément
de contenu est un block typé (paragraph, header, list, image…) sérialisé
en JSON. Même UX globale (arbo + éditeur full-page + tags + recherche),
mais pas de wiki-links / graph.

- [Vue d'ensemble & décisions](block/overview.md)
- (entity / manager / editor à détailler une fois le sous-module
  Markdown stabilisé)

## Architecture commune

- **Entités séparées** `MarkdownNote` et `BlockNote` (pas de
  discriminator) — voir justifications dans
  [`block/overview.md`](block/overview.md). Si plus tard une vue unifiée
  "toutes mes notes" devient utile, extraire les champs communs
  (`tags`, `user`, `parent`, `position`) dans une abstract partagée.
- **Réutilisations directes** côté Block depuis Markdown :
  - Type Doctrine encrypted (`markdown/encryption.md`)
  - Controller images (`markdown/images.md`)
  - Pattern Manager 5-couches + voter ownership
  - Composable `useNoteTree.js` (drag-drop arbo)
  - i18n base
- **Ordre d'exécution** : démarrer par Markdown (besoin existant, port
  d'Onyx). Block vient ensuite et hérite de l'infra.
