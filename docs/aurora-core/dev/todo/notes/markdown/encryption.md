# Notes markdown — Chiffrement at-rest

- [ ] **Décision** : `title` et `content` sont chiffrés au repos dans Onyx (cast Eloquent `encrypted`). Choix Aurora :
  - **Option A — Type Doctrine custom** `EncryptedStringType` / `EncryptedTextType` réutilisable par toute entité Aurora. Transparent pour le Manager (encrypt/decrypt automatique).
  - **Option B — Chiffrement explicite dans le Manager** via un service `NoteEncryptionService` (pattern de réf : `MountPointEncryptionService`).
  - **Recommandé** : Option A si on prévoit d'autres entités sensibles (probable). Sinon B.
- [ ] **Clé** : utiliser `APP_SECRET` Symfony ou clé dédiée `NOTES_ENCRYPTION_KEY` ? Une clé dédiée permet la rotation indépendante.
- [ ] **Indexation** : le chiffrement empêche la recherche full-text SQL. Acceptable au démarrage (recherche côté front sur contenu décrypté). À revoir si volumes > qq centaines de notes par user.
- [ ] **Tests** : round-trip encrypt/decrypt + vérifier que le champ stocké en base est bien chiffré (pas en clair).
