# Notes markdown — Chiffrement at-rest

**Statut : ✅ Fait** (commit `324dd51b` — feat: add Aurora encryption service + Doctrine types)

- [x] **Décision** : Option A — Types Doctrine custom `EncryptedStringType` + `EncryptedTextType` dans `src/Core/Encryption/Doctrine/`. Transparent pour le Manager (encrypt sur `convertToDatabaseValue`, decrypt sur `convertToPHPValue`). Réutilisable par toute entité Aurora.
- [x] **Clé** : `AURORA_ENCRYPTION_KEY` dédiée (base64 32 bytes, libsodium XSalsa20-Poly1305). Distincte d'`AURORA_MOUNT_POINT_KEY` pour rotation indépendante.
- [x] **Service** : `Aurora\Core\Encryption\Service\EncryptionService` + interface (#[AsAlias]).
- [x] **Bootstrapper** : `EncryptedTypeBootstrapper` injecte le service dans les types statiques au boot (`kernel.request` + `console.command` priorité 9999).
- [x] **Types DBAL** enregistrés via `AuroraBundle::prependExtension`.
- [x] **Tests** : 7 tests unitaires `EncryptionService` + 3 tests `EncryptedTextType` (round-trip + tamper + boot order).
- [x] **Vérifié en intégration** : test `testCreatedRowOnDatabaseIsEncrypted` lit la table SQL brute et confirme que title/content ne sont pas en clair.

## Limites connues

- **Recherche full-text SQL** impossible (par design). La recherche dans
  la sidebar tourne côté front sur le contenu décrypté chargé pour le
  graph/backlinks. Acceptable jusqu'à ~qq centaines de notes/user. À
  revoir si volume.
