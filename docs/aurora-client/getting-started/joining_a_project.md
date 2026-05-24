# Démarrer sur un projet aurora-client existant — quickstart 10 min

Tu viens de `git clone` un projet client Aurora (aurora-welding,
aurora-client, autre fork) et tu veux le faire tourner en local. Ce doc
te guide étape par étape.

> 📘 Pour **créer un nouveau projet** à partir d'aurora-client (forker
> le template), voir [`setup.md`](setup.md) section "Démarrer un nouveau
> projet" — ce doc-ci suppose que le projet existe déjà et qu'on rejoint
> l'équipe.

---

## Prérequis

| Outil | Version min | Vérifier |
|---|---|---|
| PHP | 8.4 | `php -v` |
| Composer | 2.x | `composer --version` |
| PostgreSQL | 16+ | `psql --version` (en local — sinon Docker, cf. §3 B) |
| Node.js | 24+ | `node -v` |
| pnpm | 10+ | `pnpm --version` |
| Symfony CLI | latest | `symfony version` (pour `make start`) |

Manque quelque chose ? Checklist exhaustive : [`../../aurora-core/ops/prerequisites.md`](../../aurora-core/ops/prerequisites.md).

---

## 1. Installer les dépendances

```bash
composer install                                # vendor PHP (aurora-core + Symfony deps)
pnpm install                                    # deps JS du client (Vue, axios, modules métier…)
(cd vendor/axelraboit/aurora && pnpm install)   # tooling Vite/Vitest dans le vendor
```

> Pourquoi 2× `pnpm install` ? Le client a son `package.json` (runtime
> Vue deps) et le vendor aurora a le sien (build tooling vite/vitest).
> Vite résout les deux via Node walk-up.

---

## 2. Configurer l'environnement

```bash
cp .env.local.example .env.local
```

Éditer `.env.local` — variables **obligatoires** :

```dotenv
APP_SECRET=<32-char-random>
DATABASE_URL=postgresql://<user>:<password>@127.0.0.1:5432/<db_name>?serverVersion=16&charset=utf8
AURORA_MOUNT_POINT_KEY=<base64-32-bytes>
AURORA_ENCRYPTION_KEY=<base64-32-bytes>
```

Générer les clés base64 :

```bash
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"
```

> ⚠️ `AURORA_ENCRYPTION_KEY` doit être **stable** dans la durée — si
> tu la changes après avoir saisi des données chiffrées (MountPoints,
> tokens), elles deviennent illisibles. À générer une fois et stocker
> dans un vault d'équipe.

Idem `.env.test.local` (utilisé par `make ft`) :

```bash
cp .env.local .env.test.local
```

Éditer `.env.test.local` pour pointer sur une DB de test distincte (ex:
`<db_name>_test` — note que Doctrine ajoute aussi un suffixe automatique
en mode test).

---

## 3. Setup base de données

> ⚠️ **Ne PAS faire `make install-dev` ni `make migrate` directement**
> sur une DB fresh — les migrations multi-namespace
> (`ClientMigrations` + `DoctrineMigrations` du vendor) ne s'interleavent
> pas bien sur DB vierge. Procédure ci-dessous = équivalent fonctionnel
> en plus court et fiable.

### Option A — PostgreSQL local

```bash
# Vérifier que ta DB ciblée par DATABASE_URL existe (ou la créer)
psql -h 127.0.0.1 -U <user> -d postgres -c "CREATE DATABASE <db_name>;"
```

### Option B — Docker (si tu n'as pas PG local)

```bash
make docker-up      # docker-compose up postgres
```

### Init du schéma + state migrations + données

Quelle que soit l'option, ensuite :

```bash
# 1. Schéma depuis les entités Doctrine (vendor + client)
php bin/console doctrine:schema:create

# 2. Init metadata storage + marquer toutes les migrations comme appliquées
php bin/console doctrine:migrations:sync-metadata-storage --no-interaction
php bin/console doctrine:migrations:version --add --all --no-interaction

# 3. Sanity check
php bin/console doctrine:schema:validate

# 4. Settings + privileges + menus du registre Aurora
php bin/console aurora:application-parameter
php bin/console aurora:privileges:sync
php bin/console aurora:menus:sync

# 5. Optionnel : charger les fixtures de dev (users, données démo)
php bin/console doctrine:fixtures:load --no-interaction
```

Détails du *pourquoi* dans [`../dev/database.md`](../dev/database.md)
section "DB fresh : `make migrate` ne marche pas".

---

## 4. Build initial des assets

```bash
make build
```

Premier `make build` après clone → écrit `public/build/entrypoints.json` +
le manifest Vite. Sans ce build initial, le serveur Symfony rendrait
des pages sans `<script>` ni `<link>` (assets pas inclus).

> ⚠️ Si `make build` réussit mais que `ls public/build/` retourne
> "No such file or directory", vérifier le symlink :
> ```bash
> ls -la public/build
> # doit pointer vers: public/build -> ../vendor/axelraboit/aurora/public/build
> ```
> Si manquant, le restaurer : `ln -s ../vendor/axelraboit/aurora/public/build public/build`.
> Cf. [`../dev/assets_vue.md`](../dev/assets_vue.md) §Symlink pour le contexte.

---

## 5. Démarrer le dev serveur

```bash
make start
```

Lance en parallèle :
- Serveur Symfony sur https://localhost:8000 (ou le port libre dispo)
- Vite en mode watch (HMR sur les Vue components)

Ouvrir le navigateur sur l'URL affichée. Login admin via les fixtures :

| Champ | Valeur |
|---|---|
| Email | `marie.dupont@aurora.app` (ou autre admin selon les fixtures du projet — chercher `ROLE_ADMIN` ou `ROLE_DEV` en DB) |
| Mot de passe | `password` (convention fixtures aurora) |

```bash
# Si tu doutes du compte admin :
psql -d <db_name> -c "SELECT email, roles FROM core_users WHERE roles::text LIKE '%ADMIN%' OR roles::text LIKE '%DEV%';"
```

---

## 6. Vérifier que tout marche

```bash
make ft            # fix (linters) + tests PHP + JS
```

Tous les tests doivent passer. Si plantage côté DB test → vérifier
`.env.test.local` (DATABASE_URL pointe sur une DB de test accessible).

Si la DB test n'existe pas encore, refaire la séquence du §3 sur la DB
test (en lançant les commandes avec `--env=test`).

---

## Troubleshooting

### "Asset not found" ou page sans CSS/JS

Cf. §4 — symlink `public/build` manquant. Restaurer + `make build`.

### `AURORA_ENCRYPTION_KEY must be a base64-encoded 32-byte key`

`.env.local` (ou `.env.test.local`) n'a pas cette variable. Cf. §2.

### `FATAL: password authentication failed for user "app"`

`.env.test.local` utilise les creds du template (`app` / `!ChangeMe!`)
qui ne matchent pas ta PG locale. Mettre tes vrais creds.

### `relation "core_<table>" does not exist` pendant les migrations

Tu as lancé `make migrate` au lieu du workaround du §3. Drop la DB et
recommence le §3 du début.

### `make ft` plante sur `make db-test`

`.env.test.local` mal configuré (creds ou DB qui n'existe pas). Cf. §2.

---

## Workflow quotidien après setup

| Commande | Effet |
|---|---|
| `make start` | Re-démarre Symfony + Vite |
| `make ft` | Fix linters + tests |
| `make fixtures` | Reset DB + reload fixtures |
| `make pull-update` | Récup les commits du collègue + sync deps + migrations |
| `make aurora-update` | Bump aurora-core à sa dernière version |

Doc détaillée : [`../dev/dev_workflow.md`](../dev/dev_workflow.md).
