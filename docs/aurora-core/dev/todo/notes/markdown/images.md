# Notes markdown — Images

**Statut : ✅ Fait (2026-05-16).** Backend (service + controller + cleanup orphelines) et frontend (drag-drop + Ctrl+V paste) opérationnels.

## Implémentation

- **`MarkdownNoteImageService`** (`Module/Notes/Markdown/Service/`) — store/path/delete/extractFilenames.
  Storage `{kernel.project_dir}/var/uploads/notes-markdown/{userId}/{uuid}.{ext}`,
  hors document root. `final readonly`. Filesystem injecté pour stub
  facile en test.
- **`NoteImageMimeEnum`** — single source of truth pour les MIME acceptés
  (png/jpeg/webp/gif). `extension()` → `.png`/`.jpg`/`.webp`/`.gif`.
- **`MarkdownNotesImagesController`** — `/backend/notes/markdown/images/{upload,/{filename}}`.
  Auth `notes.markdown.use`, `BinaryFileResponse` privé + cache 1h pour
  le serve. Filename whitelisté regex `[A-Za-z0-9._-]+` côté route.
- **Cleanup orphelines** — hook `MarkdownNoteManager::cleanupOrphanedImages()`
  appelé depuis `update` (diff old/new) et `delete` (drop tout). Le
  manager prend `MarkdownNoteImageService` au constructor.
- **Composable Vue** `useNoteImageUpload` — drop + paste sur le textarea,
  POST multipart, splice `![filename](url)` au caret. Réutilise
  `applyInsert` (nouveau, exposé par `useNoteEditorTextarea`). Tout le
  lifecycle (onMounted/onBeforeUnmount/watch textareaRef) vit dans le
  composable lui-même.
- **Resize client-side** `useNoteImageResize` — cap au plus grand côté
  à 2048px (configurable), ré-encodage WebP qualité 0.85. Évite
  d'envoyer des originaux 5–10 MB pour rien. GIF skippé (toBlob
  perdrait l'animation), images sous le cap aussi. Échec silencieux
  → fallback sur le fichier d'origine (resize est une optimisation,
  pas un gate). Utilise `OffscreenCanvas` quand dispo, fallback DOM.
- **Drag-to-resize (preview)** — chaque image est wrappée dans
  `<span class="note-image-wrap">` avec une poignée coin
  `.note-image-handle` (Obsidian/Onyx style). Drag = `pointerdown`
  capté par `NotePreview`, mousemove met à jour `image.style.width`
  visuel, mouseup émet `image-resize` (src + new width). Le
  `useNotesEditor.onImageResize` réécrit la source markdown via
  `updateImageDimensionInContent` qui ajoute/remplace le suffixe
  `|width` dans le alt (`![alt|320](url)` — syntaxe Obsidian).
  Le re-rendu pick-up automatiquement le nouveau width depuis le alt
  via l'extension marked `markedImageDimensions`. Aspect ratio
  préservé (`height: auto`), largeur min 40px côté UI.
- **Réglages admin** — `MarkdownNoteSettingEnum` + `NotesMarkdownConfigurationTabProvider`
  exposent `ImageMaxEdge` (int, défaut 2048 px) et `ImageQualityPct`
  (int 0-100, défaut 85) dans un onglet "Notes" de `/backend/settings`
  (priorité 110). `MarkdownNotesViewBuilder` lit les valeurs via
  `SettingRepository::getOrDefault()`, clamp la qualité à [0,1], et
  passe le tout en props Vue (`imageMaxEdge`, `imageQuality`) qui
  descendent jusqu'à `useNoteImageResize`. Traductions fr/en dans
  `Module/Notes/translations/messages.{fr,en}.yaml` (label/description
  des params + label de l'onglet dans `Core/Setting/translations/`).
- **CSS preview** — `.note-preview img { max-width: 100%; height: auto; border-radius: 4px; }`.
- **Tests** — `NoteImageServiceTest` (9), `MarkdownNoteImagesControllerTest` (4),
  `MarkdownNoteManagerTest` (+2 pour le cleanup orphelines).

## Non implémenté (peut venir plus tard)

- Quota par user (`AURORA_NOTES_IMAGE_QUOTA_MB`).
- Migration vers une entity dédiée si besoin de métadonnées (alt, dimensions, hash).

---

## Spec historique (avant implémentation)

Feature requise pour usage réel (drag-drop d'images depuis le presse-papier était core dans Onyx).

## Backend

- [ ] **Entity / Storage** : décider du chemin :
  - **A** : table dédiée `markdown_note_images` (image entity avec note FK)
  - **B** : stockage filesystem sans entity (path-based, plus simple, moins traçable)
  - Onyx fait B. Pour Aurora : démarrer en B, migrer vers A si besoin de
    métadonnées (alt, dimensions, hash, etc).
- [ ] **Service** `Markdown\Service\MarkdownNoteImageService` :
  - `store(uploadedFile, ownerUser): string` → retourne le filename pour
    insertion dans le markdown.
  - `path(filename, ownerUser): string` → chemin disk pour serve.
  - `delete(filename, ownerUser): void`
  - Naming : `{userId}/{uuid}.{ext}` pour scoping naturel.
- [ ] **Controller** `Backend/MarkdownNotesImagesController` :
  - `POST /backend/notes/markdown/images/upload` — multipart, retourne `{url, filename}`.
  - `GET /backend/notes/markdown/images/{filename}` — serve avec contrôle
    d'accès (vérifier que le filename appartient au user courant via le
    préfixe path). **Pas** de serve nginx direct → fuite cross-user.
- [ ] **Sécurité** :
  - Whitelist MIME (image/png, image/jpeg, image/webp, image/gif).
  - Max size (5 MB par défaut, configurable).
  - Rename à un UUID côté serveur (jamais le nom client).
  - Path traversal : valider que `realpath(file)` reste sous le storage root.
- [ ] **Cleanup orphelines** — au `update` ou `delete` de note, scanner
      le `oldContent` et `newContent` avec regex
      `/\/backend\/notes\/markdown\/images\/([^\s\)\"\']+)/` et delete
      les fichiers qui n'apparaissent plus. Logique dans
      `MarkdownNoteManager::update/delete` (hook protected
      `cleanupOrphanedImages`).
- [ ] **Tests** : upload + serve + cleanup + auth (vérifier qu'un user
      ne peut pas accéder aux images d'un autre).

## Frontend

- [ ] **Composable `useNoteImageUpload.js`** à porter depuis Onyx :
  - Drag & drop sur le textarea
  - Collage `Ctrl+V` depuis le presse-papier (event `paste`)
  - Insertion du markdown `![alt](url)` à la position du curseur après upload réussi
- [ ] **Composable `useNoteImageResize.js`** (optionnel) — resize côté
      client avant upload pour éviter d'envoyer des originaux 20 MB.
      Canvas + `toBlob({type: 'image/webp', quality: 0.85})`. Cap
      dimensions à 2048px.
- [ ] **CSS preview** : `.note-preview img { max-width: 100%; height: auto; border-radius: 4px; }` dans `assets/css/modules/notes/markdown/preview.css`.

## Storage path & quota

- [ ] **Storage** : `var/uploads/notes-markdown/{userId}/{uuid}.{ext}`
      (sous le project_dir, exclu du document_root). Si on a un service
      de stockage Aurora central (S3 / disk abstrait), utiliser celui-là.
- [ ] **Quota** (optionnel v1) : plafond par user (config
      `AURORA_NOTES_IMAGE_QUOTA_MB`), refuser l'upload si dépassé.
      Compter via une scalar query sur la taille des fichiers du user.

## Pattern à porter

- Le composant `NoteImageController` Laravel d'Onyx fait le serve avec
  `response()->file(...)` après auth check. Aurora utilisera la même
  approche via `BinaryFileResponse`.
- Le `useNoteImageUpload` d'Onyx attache les listeners au textarea
  (drop + paste) et émet `(markdownToInsert, cursorOffset)` pour que
  le SFC fasse l'insertion.
