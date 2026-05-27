---
name: Vault module — état et particularités
description: Module Vault (gestionnaire mots de passe E2E) ajouté le 2026-05-09 — 3 entités, architecture non-standard pour un module utilisateur
type: project
---

Coffre-fort ajouté le 2026-05-09 (3 entités : VaultEntry, VaultFolder, VaultUserConfig). **Depuis 2026-05 c'est un OUTIL du module Tools** : code sous `src/Module/Tools/Vault/` (`Aurora\Module\Tools\Vault\*`), pas un module à part. Voir [[project_url_namespacing_backlog]] §Module Tools.

**Why:** Portage de l'app Warden (gestionnaire de mots de passe standalone) en module Aurora, chiffrement E2E côté client.

**Particularités vs modules CRM/GED standard :**
- Pas de permission granulaire view/create/edit/delete — une seule permission `tools.vault.use` (vault = données personnelles de l'utilisateur, pas de gestion admin)
- Les controllers utilisent `$this->getUser()` pour tout scoper (jamais de ParamConverter `VaultEntry` directement — ownership check manuel via repository avec `findOneByUserAndId`)
- `VaultUserConfigManager` a un seul hook `setup()` (pas de create+update) — la config est immutable après initialisation
- Route principale : `GET /backend/tools/vault` → `VaultEntriesController::index()` (pas de separate entry point)
- `VaultEntriesViewBuilder` charge entries + folders + config en une seule passe (anti-N+1)

**Chiffrement :**
- PBKDF2-SHA256 (600 000 iterations) + AES-256-GCM
- Salt (128 bits) stocké dans VaultUserConfig::argon2Salt — jamais la clé dérivée
- IV (96 bits) unique par entrée, stocké en clair dans VaultEntry::iv
- Le titre et l'URL sont en clair (affichage liste), le reste dans encryptedData

**How to apply:** Si on ajoute des features au Vault, respecter que c'est utilisateur-scopé et que le serveur ne voit jamais de données en clair sauf title/url/type. Pour les nouvelles actions, toujours vérifier l'ownership via `findOneByUserAndId`.
