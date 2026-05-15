# Notes Notion-like (EditorJS) — vue d'ensemble

Sous-module miroir du sous-module Obsidian-like, **même UX globale**
(arbo à gauche + éditeur full-page au centre + tags + recherche), mais
l'éditeur est **[EditorJS](https://editorjs.io)** (block editor type
Notion) au lieu de markdown texte.

## Pourquoi un sous-module séparé plutôt qu'un toggle dans Obsidian-like

- **Storage différent** : EditorJS sérialise en **JSON structuré** (array
  de blocks typés), pas en texte. Stocker du JSON dans le même champ
  `content` que du markdown polluerait le schéma et la logique Manager.
- **Pas de wiki-links / graph** : EditorJS n'a pas de syntaxe `[[…]]`
  native. Toute la plomberie wiki-links + backlinks + graph d'Obsidian-like
  ne s'applique pas.
- **Cibles utilisateurs distinctes** : Obsidian-like = utilisateur tech
  (raw markdown, syntaxe) ; Notion-like = utilisateur WYSIWYG block-based
  qui ne veut pas taper de markdown.

## Ce qui est identique au sous-module Obsidian-like

- Arborescence parent/enfant illimitée
- Tags (json array)
- Recherche full-text côté front
- Ownership par user (voter Symfony)
- Convention 5-couches (Entity / DTO / Manager / Serializer / Vue)
- Chiffrement at-rest du contenu (réutiliser le Type Doctrine de
  [`../obsidian/encryption.md`](../obsidian/encryption.md))
- Images upload/serve/cleanup — partager le `NoteImageController`
  d'Obsidian-like (c'est juste un stockage de fichiers)
- Templates, raccourcis clavier, sommaire (TOC)

## Ce qui change

| Aspect | Obsidian-like | Notion-like (EditorJS) |
|---|---|---|
| Champ contenu | `content` text encrypted | `content_blocks` json encrypted |
| Rendu preview | marked.js + extensions | EditorJS renderer (ou `editorjs-html`) |
| Wiki-links | ✅ `[[…]]` | ❌ |
| Backlinks / mentions | ✅ | ❌ |
| Graph view | ✅ | ❌ |
| Slash commands | ✅ custom | ✅ **natif** EditorJS (`+` menu) |
| Callouts | ✅ extension marked | ✅ plugin `editorjs-alert` |
| Code blocks | ✅ extension marked | ✅ plugin `editorjs-code` |
| Listes à cocher | ✅ extension marked | ✅ plugin `editorjs-checklist` |

## Décisions structurelles

- [x] **Entité séparée** `NotionNote` (pas de discriminator avec
      `ObsidianNote`). Justification : champ contenu de type différent
      (json vs text), pas de wiki-link cache, arbo propre à chaque
      sous-module.
- [x] **Choix EditorJS** confirmé. Bibliothèque mature, plugins riches,
      storage JSON lisible / portable.
- [ ] **Plugins EditorJS** à shortlister : header, paragraph, list,
      checklist, quote, code, table, image, embed, alert, delimiter,
      marker, inline-code, link.
- [ ] **Compat mobile** : EditorJS a une UX mobile correcte mais à
      tester avant de s'engager.

## Sous-tâches (à détailler une fois Obsidian-like stabilisé)

- Entity & schéma (mêmes champs qu'`ObsidianNote` sauf `content` →
  `content_blocks` json encrypted)
- Manager & Serializer (réutiliser le pattern Obsidian-like ; supprimer
  la logique wiki-links/graph)
- Éditeur Vue : intégrer `@editorjs/editorjs` + plugins, wrapper Vue 3
  custom (ou lib existante), gérer save debounced du JSON

## Ordre d'exécution

Démarrer **après** le sous-module Obsidian-like stabilisé. Réutilisations
directes :
- Type Doctrine encrypted (`../obsidian/encryption.md`)
- Controller images (`../obsidian/images.md`)
- Pattern Manager + voter ownership (`../obsidian/manager.md`)
- Composable `useNoteTree.js` (drag-drop arbo) — strictement identique
- i18n base notes
