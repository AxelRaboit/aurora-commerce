# Notes — Manager & Serializer

**Statut : ✅ Fait** (commits `8fa39364` + `7d0e63b2` + `e747a402` + `fca34119`)

- [x] **`MarkdownNoteManager`** non-`final`, props `protected readonly`, `#[AsAlias(MarkdownNoteManagerInterface::class)]`. Logique portée de `onyx/app/Services/NoteService.php`.
  - Méthodes publiques : `create`, `update`, `move`, `reorder`, `delete`
  - Méthodes de lecture : `backlinks`, `unlinkedMentions`, `graph`
  - Hook d'instanciation : `protected createNote(): MarkdownNoteInterface`
  - Hook hydratation : `protected applyInput(MarkdownNoteInterface, MarkdownNoteInputInterface)`
  - Hooks audit : `auditCreated/Updated/Deleted` + `auditPayload`
  - **Rename auto wiki-links** sur changement de titre (cf. `renameWikiLinks` protected, regex `[[oldTitle]] → [[newTitle]]`)
- [x] **`MarkdownNoteSerializer`** non-`final` + `#[AsAlias]`. Variante liste (sans `content`) vs détail (avec `content`).
- [x] **Controller `Backend/MarkdownNotesController`**. Routes :
  - `GET /backend/notes/markdown` (page Twig)
  - `GET /list` (JSON list, no content)
  - `GET /{id}` (JSON detail)
  - `POST /create`, `/{id}/update`, `/{id}/delete`, `/{id}/move`
  - `GET /{id}/backlinks`, `/{id}/unlinked-mentions`, `/graph`
  - `POST /reorder` (bulk tree, atomic cycle detection)
- [x] **Ownership** : implicite via `findOneByUserAndId(user, id)` partout — pattern Vault (pas de voter dédié).
- [x] **Cycle detection** : `MarkdownNoteHierarchyService` extrait pour `move`; bulk `reorder` détecte les cycles en pré-flight côté Manager.
- [x] **Tests** unitaires + intégration (CRUD + ownership + encryption at-rest + cycle).

## Restant (à faire séparément)

- Controller `Backend/MarkdownNoteImageController` — cf. [`images.md`](images.md)
