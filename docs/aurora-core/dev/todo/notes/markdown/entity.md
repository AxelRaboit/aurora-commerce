# Notes — Entity & schéma

**Statut : ✅ Fait** (commits `49e7a056` + `a8673ea2`)

- [x] **Entity `MarkdownNote`** triplet 5-couches : `MarkdownNoteInterface` + `AbstractMarkdownNote` (MappedSuperclass) + `MarkdownNote` concrete non-`final`. Sequence `seq_core_markdown_note_id`. Référencé dans `AuroraBundle::$resolve_target_entities`.
  - Champs : `id`, `parent` (self-ref nullable, set null on delete), `user` (cascade delete), `title` (text encrypted), `content` (longtext encrypted), `tags` (json), `position` (uint, défaut 0), `createdAt`, `updatedAt`, `agency` (snapshot du user au create)
- [x] **DTO** `MarkdownNoteInput` + `MarkdownNoteInputInterface` + `MarkdownNoteInputFactory(Interface)` avec `#[AsAlias]`. Propriétés `public readonly`.
- [x] **Repository** `MarkdownNoteRepository` étend `ResolveTargetEntityRepository`. Méthodes : `findFlatListForUser` (sans `content`), `findAllWithContentForUser` (avec `content`, pour graph/backlinks/mentions), `findOneByUserAndId`, `findMaxPositionForUserAndParent`.
- [x] **Scope** : per-user avec snapshot `Agency` au create (décidé en début de projet).
- [x] Migration `Version20260515180019` créée et appliquée.
- [x] Demo fixtures (Welcome / Getting Started / Tasks / Random thoughts).
