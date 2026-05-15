# Notes — Entity & schéma

- [ ] **Entity `Note`** triplet 5-couches : `NoteInterface` + `AbstractNote` (MappedSuperclass) + `Note` concrete non-`final`. Sequence `seq_core_note_id`. Référencer dans `AuroraBundle::$resolve_target_entities`.
  - Champs : `id`, `parent` (self-ref nullable, set null on delete), `user` (cascade delete), `title` (text, chiffré), `content` (longtext, chiffré), `tags` (json), `position` (uint, défaut 0), `createdAt`, `updatedAt`
  - Source schéma : `onyx/database/migrations/2026_04_13_000001_create_notes_table.php`
- [ ] **DTO** `NoteInput` + `NoteInputInterface` + `NoteInputFactory(Interface)` avec `#[AsAlias]`. Propriétés `public readonly` (PAS `readonly class`).
- [ ] **Repository** `NoteRepository` étend `ResolveTargetEntityRepository`. Méthodes : `findFlatListForUser` (sans `content`), `findAllForUser` (avec `content`, pour graph/backlinks).
- [ ] **Scope multi-tenant** : décider per-user (Onyx) vs per-agency (Aurora) avant d'écrire l'entité.
