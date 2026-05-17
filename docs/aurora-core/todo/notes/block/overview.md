# Notes Block (EditorJS) — vue d'ensemble

Sous-module miroir du sous-module Markdown, **même UX globale** (arbo à
gauche + éditeur full-page au centre + tags + recherche), mais l'éditeur
est **[EditorJS](https://editorjs.io)** : chaque élément de contenu est
un block typé (paragraph, header, list, image, code…) sérialisé en JSON
au lieu d'être du markdown texte.

## Pourquoi un sous-module séparé plutôt qu'un toggle dans Markdown

- **Storage différent** : EditorJS sérialise en **JSON structuré** (array
  de blocks typés), pas en texte. Stocker du JSON dans le même champ
  `content` que du markdown polluerait le schéma et la logique Manager.
- **Pas de wiki-links / graph** : EditorJS n'a pas de syntaxe `[[…]]`
  native. Toute la plomberie wiki-links + backlinks + graph du
  sous-module Markdown ne s'applique pas.
- **Cibles utilisateurs distinctes** : Markdown = utilisateur tech (raw
  markdown, syntaxe) ; Block = utilisateur WYSIWYG block-based qui ne
  veut pas taper de markdown.

## Ce qui est identique au sous-module Markdown

- Arborescence parent/enfant illimitée
- Tags (json array)
- Recherche full-text côté front
- Ownership par user (voter Symfony)
- Convention 5-couches (Entity / DTO / Manager / Serializer / Vue)
- Chiffrement at-rest du contenu (réutiliser le Type Doctrine de
  [`../markdown/encryption.md`](../markdown/encryption.md))
- Images upload/serve/cleanup — partager le `NoteImageController` du
  sous-module Markdown (c'est juste un stockage de fichiers)
- Templates, raccourcis clavier, sommaire (TOC)

## Ce qui change

| Aspect | Markdown | Block (EditorJS) |
|---|---|---|
| Champ contenu | `content` text encrypted | `contentBlocks` json encrypted |
| Rendu preview | marked.js + extensions | EditorJS renderer (ou `editorjs-html`) |
| Wiki-links | ✅ `[[…]]` | ❌ |
| Backlinks / mentions | ✅ | ❌ |
| Graph view | ✅ | ❌ |
| Slash commands | ✅ custom | ✅ **natif** EditorJS (`+` menu) |
| Callouts | ✅ extension marked | ✅ plugin `editorjs-alert` |
| Code blocks | ✅ extension marked | ✅ plugin `editorjs-code` |
| Listes à cocher | ✅ extension marked | ✅ plugin `editorjs-checklist` |

## Décisions structurelles

- [x] **Entité séparée** `BlockNote` (pas de discriminator avec
      `MarkdownNote`). Justification : champ contenu de type différent
      (json vs text), pas de wiki-link cache, arbo propre à chaque
      sous-module.
- [x] **Choix EditorJS** confirmé. Bibliothèque mature, plugins riches,
      storage JSON lisible / portable.
- [ ] **Plugins EditorJS** à shortlister : header, paragraph, list,
      checklist, quote, code, table, image, embed, alert, delimiter,
      marker, inline-code, link.
- [ ] **Compat mobile** : EditorJS a une UX mobile correcte mais à
      tester avant de s'engager.

## Sous-tâches (à détailler une fois Markdown stabilisé)

- Entity & schéma (mêmes champs que `MarkdownNote` sauf `content` →
  `contentBlocks` json encrypted)
- Manager & Serializer (réutiliser le pattern Markdown ; supprimer la
  logique wiki-links/graph)
- Éditeur Vue : intégrer `@editorjs/editorjs` + plugins, wrapper Vue 3
  custom (ou lib existante), gérer save debounced du JSON

## Ordre d'exécution

Démarrer **après** le sous-module Markdown stabilisé. Réutilisations
directes :
- Type Doctrine encrypted (`../markdown/encryption.md`)
- Controller images (`../markdown/images.md`)
- Pattern Manager + voter ownership (`../markdown/manager.md`)
- Composable `useNoteTree.js` (drag-drop arbo) — strictement identique
- i18n base notes
