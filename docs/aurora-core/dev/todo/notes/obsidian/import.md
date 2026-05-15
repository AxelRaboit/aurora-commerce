# Notes markdown — Import depuis Onyx

- [ ] **Commande Symfony** `aurora:notes:import-from-onyx` one-shot pour migrer le contenu utilisateur existant.
  - Lit la base Onyx (SQLite/MySQL) en read-only
  - Décrypte `title` / `content` avec la clé Laravel (`APP_KEY` Onyx)
  - Re-chiffre avec le mécanisme Aurora (cf. [`encryption.md`](encryption.md))
  - Copie les images de `onyx/storage/app/...` vers le storage Aurora
  - Mappe `user_id` Laravel → `user_id` Aurora **via email** (les IDs Laravel ne sont pas portables)
  - Préserve `parent_id`, `position`, `tags`, `created_at`, `updated_at`
  - Dry-run + rapport (X notes, Y images, Z users introuvables) avant commit
- [ ] **Idempotent** : pouvoir relancer sans dupliquer (clé unique sur `(user_id, title, created_at)` ou marqueur d'import).
- [ ] **Cleanup post-import** : vérifier qu'aucune image n'est orpheline (cf. logique cleanup `images.md`).
