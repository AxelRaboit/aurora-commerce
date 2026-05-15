# Notes — sous-modules

Le module `src/Module/Notes/` regroupe deux sous-modules de prise de
notes, partageant l'infra (arborescence, tags, recherche, images,
chiffrement, ownership) mais avec deux UX distinctes :

- **Obsidian-like** — éditeur Markdown + wiki-links + graph
- **Notion-like** — éditeur block-based EditorJS

## Sous-modules

### [`obsidian/`](obsidian/) — Notes Obsidian-like (port d'Onyx)

Éditeur Markdown complet façon Obsidian : wiki-links `[[…]]`, graph view,
callouts, slash commands, templates, images drag-drop. Source à porter :
`/home/axel/Documents/dev/personal/onyx/`.

- [Entity & schéma](obsidian/entity.md)
- [Manager & Serializer](obsidian/manager.md)
- [Éditeur Vue](obsidian/editor.md)
- [Wiki-links & graph](obsidian/wiki-links.md)
- [Images](obsidian/images.md)
- [Chiffrement at-rest](obsidian/encryption.md)
- [Script d'import depuis Onyx](obsidian/import.md)

### [`notion/`](notion/) — Notes Notion-like (EditorJS)

Éditeur block-based façon Notion via [EditorJS](https://editorjs.io).
Même UX globale qu'Obsidian-like (arbo + éditeur full-page + tags +
recherche), mais contenu structuré en blocks JSON au lieu de markdown
texte. Pas de wiki-links / graph.

- [Vue d'ensemble & décisions](notion/overview.md)
- (entity / manager / editor à détailler une fois le sous-module
  Obsidian stabilisé)

## Architecture commune

- **Entités séparées** `ObsidianNote` et `NotionNote` (pas de
  discriminator) — voir justifications dans
  [`notion/overview.md`](notion/overview.md). Si plus tard une vue
  unifiée "toutes mes notes" devient utile, extraire les champs communs
  (`tags`, `user`, `parent`, `position`) dans une abstract partagée.
- **Réutilisations directes** côté Notion-like depuis Obsidian-like :
  - Type Doctrine encrypted (`obsidian/encryption.md`)
  - Controller images (`obsidian/images.md`)
  - Pattern Manager 5-couches + voter ownership
  - Composable `useNoteTree.js` (drag-drop arbo)
  - i18n base
- **Ordre d'exécution** : démarrer par Obsidian-like (besoin existant,
  port d'Onyx). Notion-like vient ensuite et hérite de l'infra.
