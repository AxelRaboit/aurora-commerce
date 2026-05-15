# Notes markdown — Images

- [ ] **Upload** — endpoint `POST /backend/notes/images` (drag & drop + collage Ctrl+V depuis le presse-papier). Composable `useNoteImageUpload.js` à porter.
- [ ] **Serve** — endpoint `GET /backend/notes/images/{filename}` avec contrôle d'accès Symfony (pas de serve direct nginx → fuite cross-user). Décider du chemin de stockage : `var/uploads/notes/{userId}/...` ou via service de stockage Aurora existant.
- [ ] **Resize / preview** — composable `useNoteImageResize.js` à porter (resize côté client avant upload).
- [ ] **Cleanup orphelines** — quand une note est mise à jour ou supprimée, supprimer les fichiers images plus référencés dans le contenu. Regex `/\/notes\/images\/([^\s\)\"\']+)/` (cf. `NoteService::deleteOrphanedImages` dans Onyx). Logique dans `NoteManager::update` et `NoteManager::delete`.
- [ ] **Quota** : décider si on plafonne le stockage par user (à voir avec le scope multi-tenant — cf. `entity.md`).
