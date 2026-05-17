# Changelog Aurora-core

Format : [SemVer](https://semver.org). Section **"Dans aurora-client"** = ce que les
projets clients doivent répercuter après avoir lancé `make aurora-update`.

---

## [Unreleased]

### ⚠️ Cassant — namespaces Core déplacés sous leur module parent

Alignement de `src/Core/` sur la convention Vault-style déjà en place
côté `src/Module/` : les sous-modules Core vivent désormais dans un
sous-dossier de leur module parent (`Aurora\Core\Platform\User`,
`Aurora\Core\Configuration\Setting`, etc.). Voir
[`MIGRATION_0.4.md`](docs/aurora-client/MIGRATION_0.4.md) pour la table
de correspondance + le `sed` bulk.

| Avant | Après |
|---|---|
| `Aurora\Core\Dashboard\*` | `Aurora\Core\General\Dashboard\*` |
| `Aurora\Core\Profile\*` | `Aurora\Core\General\Profile\*` |
| `Aurora\Core\Search\*` | `Aurora\Core\General\Search\*` |
| `Aurora\Core\Audit\*` | `Aurora\Core\Dev\Audit\*` |
| `Aurora\Core\Setting\*` | `Aurora\Core\Configuration\Setting\*` |
| `Aurora\Core\Theme\*` | `Aurora\Core\Configuration\Theme\*` |
| `Aurora\Core\Media\*` | `Aurora\Core\Media\Library\*` |
| `Aurora\Core\User\*` | `Aurora\Core\Platform\User\*` |
| `Aurora\Core\Agency\*` | `Aurora\Core\Platform\Agency\*` |
| `Aurora\Core\Auth\*` | `Aurora\Core\Platform\Auth\*` |
| `Aurora\Core\Service\{Entity,Dto,Manager,Repository,Serializer,Controller,View}\*` | `Aurora\Core\Platform\Service\{...}\*` |
| `Aurora\Core\Service\{Platform,Media,Configuration,General}Context` | inchangé (contextes cross-module) |

**Inchangé** (cross-cutting infra) : `Encryption`, `Frontend`, `Locale`,
`Mail`, `Menu`, `Migration`, `Module`, `MountPoint`, `Notification`,
`Repository`, `Scheduler`, `Sequence`, `Storage`, `Support`,
`Timestampable`, `Twig`, `Validation`.

**Aucune migration Doctrine** — les tables (`core_user`, `core_agency`,
`core_audit_log`, `core_media`, `core_setting`, etc.) gardent leur nom.

### Dans aurora-client

Lancer après `make aurora-update` :

```bash
# 1. Déplacer les dossiers d'extension (Agency, User, …) sous Core/Platform/
git mv src/Module/Core/Agency src/Module/Core/Platform/Agency

# 2. Renommer les namespaces (sed bulk — voir MIGRATION_0.4.md pour la commande complète)
grep -rl 'Aurora\\Core\\Agency\\' src tests config | xargs sed -i 's|Aurora\\Core\\Agency\\|Aurora\\Core\\Platform\\Agency\\|g'

# 3. Re-générer + valider
composer dump-autoload && make cc && make ft
```

### Ajouté
- Skills Claude Code `/add-module` et `/add-submodule` (scaffold de nouveaux
  modules / sous-features).
- Doc consolidée `docs/aurora-client/extending/extend_module.md` (remplace
  `extend_entity.md` + `custom_permissions.md` + `dev/overriding.md`).
- Convention `process_doc_audit_before_commit.md` (audit des docs/mémoires
  liées à un changement avant chaque commit).
- Glob translations élargi à depth 2 (`src/Core/*/*/translations`) pour
  supporter le nesting.

### Changé
- `extend-aurora-entity` skill : clarifie le Repository optionnel +
  rappel User-style hooks obligatoires.
- `check-extensibility` skill : ajoute check 17b (vérifier que l'absence
  d'`applyInput()` est légitimement User-style) + check 26 (audit des
  toggles de sous-modules).

---

## [0.3.0] — 2026-05-17

### Ajouté
- **Module Assistant IA** (Phase 1A + 1B) : chat synchrone avec un LLM local Ollama
  (qwen3:8b par défaut), tool-calling (`aurora_search`, `filesystem_read`,
  `filesystem_write`, `filesystem_search`, `image_read` via qwen2.5vl),
  mount-points configurables par utilisateur, flow de confirmation pour les
  actions destructives (write).
- **Onglet "Assistant" dans /backend/settings** : modèle chat, modèle vision,
  timeout HTTP, num_ctx, prompt système — tunables sans redéploiement (lecture DB
  avec fallback env).
- **`make sync-env`** + `bin/sync-client-env` : détecte les blocs
  `###> aurora/* ###` manquants dans `.env` et les insère au-dessus du divider
  CLIENT CUSTOM. Idempotent, valeurs existantes jamais touchées.
- **Divider `# === CLIENT CUSTOM ===`** dans `.env` aurora-client : sépare
  explicitement la zone gérée par aurora-core de la zone propriété du client.
- **`make sync-makefile` refusé** si Makefile a des edits non commités
  (`FORCE=1` pour forcer).
- **Tests** : +291 tests sur la période, total 2694.
- **`docs/aurora-shared/`** : nouveau dossier de docs transversales (form_validation,
  testing_php/vue, translations, scheduler, convention_seo_head) partagé entre
  aurora-core et aurora-client via vendor.
- **`docs/aurora-client/deployment/`** : guide principal + worker_systemd +
  apache_xsendfile + ocr_setup regroupés ici.
- **`docs/aurora-core/ops/prerequisites.md`** : checklist exhaustive des prérequis
  système, PHP, Ollama, vars d'env.

### Changé
- `Makefile` client : `README.md` n'est plus symlinké depuis le vendor — copié une
  seule fois à l'init, ensuite propriété du client.
- Docs : plus de symlinks `docs/aurora-*/` côté client — lecture directe dans
  `vendor/axelraboit/aurora/docs/`.
- Notes settings (Markdown + Block) : labels disambiguïsés
  ("Notes Markdown — Taille max…" vs "Notes Block — …").

### Dans aurora-client — à faire après `make aurora-update`

| Action | Commande / fichier |
|--------|-------------------|
| Ajouter les vars d'env `ASSISTANT_*` et `OCR_*` si absentes | `make sync-env` les ajoute automatiquement |
| Vérifier que `README.md` est bien un vrai fichier (plus un symlink) | `ls -la README.md` — si symlink, `make sync-claude-md` le remplace par une copie |
| Parcourir la section "CLIENT CUSTOM" de `.env` | `make sync-env` a ajouté le divider |

### Breaking changes
- Aucun changement d'API publique.

---

## [0.2.0] — 2026-05 (antérieur à ce changelog)

Établissement de la base : Posts avec éditeur bloc Editor.js, Notes Markdown
(wiki-links, graphe), Notes Block (EditorJS), Billing OCR (docTR + Ollama vision),
Galleries photo, Vault, Password Generator, extensibilité 5-couches Sylius sur 24
entités, conventions sync aurora-core → aurora-client (Makefile template, CLAUDE.md
symlink, jsconfig, security.yaml).

---

## [0.1.0] — avant 2026-05

Socle initial : Symfony 7 / PHP 8.4 / Vue 3 / Vite, modules Editorial CMS (Posts,
Taxonomies, Comments, Forms), CRM, ERP (Products), Ecommerce (Listings, Cart,
Orders), GED, HR, Planning, Project Management, auth (invitations, demandes
d'accès), thèmes, multi-langue.
