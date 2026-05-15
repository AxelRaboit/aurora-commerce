# Notes — Manager & Serializer

- [ ] **`NoteManager`** non-`final`, props `protected readonly`, `#[AsAlias(NoteManagerInterface::class)]`. Porter la logique de `onyx/app/Services/NoteService.php`.
  - Méthodes publiques : `create`, `update`, `move`, `reorder`, `delete`
  - Méthodes de lecture : `listForUser`, `backlinks`, `unlinkedMentions`, `graph`
  - Hook d'instanciation : `protected createNote(): NoteInterface`
  - Hook hydratation : `protected applyInput(NoteInterface, NoteInputInterface)` (Manager **non** User-style — CRUD simple)
  - Hooks audit : `auditCreated/Updated/Deleted` + `auditPayload`
- [ ] **`NoteSerializer`** non-`final` + `#[AsAlias]`. Variante liste (sans `content`) vs détail (avec `content`).
- [ ] **Controllers** `Backend/NoteController` + `Backend/NoteImageController`. Type-hint les interfaces. Routes : index, store, show, update, destroy, `PATCH /move`, `PATCH /reorder`, `GET /{id}/backlinks`, `GET /{id}/unlinked-mentions`, `GET /graph`, `POST /images`, `GET /images/{filename}`.
- [ ] **Ownership** : voter Symfony sur `note.user === currentUser`. Pattern de réf : `PostAccessService`.
- [ ] **Tests** Manager (create/update/move/reorder/delete, backlinks, unlinkedMentions, graph) + Controller (ownership + routes).
