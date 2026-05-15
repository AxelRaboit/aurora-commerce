# Notes markdown — Images

**Statut : ⏳ Pas commencé.** Feature requise pour usage réel (drag-drop d'images depuis le presse-papier était core dans Onyx).

## Backend

- [ ] **Entity / Storage** : décider du chemin :
  - **A** : table dédiée `markdown_note_images` (image entity avec note FK)
  - **B** : stockage filesystem sans entity (path-based, plus simple, moins traçable)
  - Onyx fait B. Pour Aurora : démarrer en B, migrer vers A si besoin de
    métadonnées (alt, dimensions, hash, etc).
- [ ] **Service** `Markdown\Service\NoteImageService` :
  - `store(uploadedFile, ownerUser): string` → retourne le filename pour
    insertion dans le markdown.
  - `path(filename, ownerUser): string` → chemin disk pour serve.
  - `delete(filename, ownerUser): void`
  - Naming : `{userId}/{uuid}.{ext}` pour scoping naturel.
- [ ] **Controller** `Backend/MarkdownNoteImagesController` :
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
