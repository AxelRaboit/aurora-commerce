# Setup — Installation locale

> **Nouveau projet ?** Si tu pars d'aurora-client pour démarrer un vrai projet,
> commence par `make install-dev` puis lance `make init-project` pour supprimer
> tous les exemples showcase. Voir la section [Démarrer un nouveau projet](#démarrer-un-nouveau-projet).

## Prérequis

| Outil | Version minimale | Notes |
|---|---|---|
| PHP | 8.4 | |
| Composer | 2.x | |
| PostgreSQL | 16+ | |
| Node.js | 20+ | |
| pnpm | 10+ | |
| php8.4-pcov | — | driver de coverage PHPUnit (optionnel, pour `--coverage`) |
| Docker (optionnel) | — | pour la base de données en local |

Installer PCOV :

```bash
sudo apt install php8.4-pcov
```

---

## 1. Cloner le dépôt

```bash
git clone git@github.com:<org>/aurora-client.git
cd aurora-client
```

---

## 2. Installer les dépendances

```bash
make install-dev
```

Cette commande fait en séquence :
1. `composer install` — installe aurora-core et toutes ses dépendances PHP
2. `pnpm install` — installe les dépendances JS côté client
3. `make fixtures` — crée la base de données, joue les migrations, charge les fixtures
4. `make sync-privileges` — synchronise les droits des modules
5. `make sync-menus` — synchronise les menus

> Si tu préfères tout faire manuellement, voir les sections ci-dessous.

---

## 3. Configurer l'environnement

Copie le fichier d'exemple et renseigne tes valeurs locales :

```bash
make setup-env
# édite ensuite .env.local
```

Variables obligatoires dans `.env.local` :

```dotenv
APP_SECRET=<chaine-aléatoire-32-chars>
DATABASE_URL=postgresql://app:password@127.0.0.1:5432/aurora_client_dev?serverVersion=16
```

Variables optionnelles (déjà définies dans `.env`, à surcharger si besoin) :

```dotenv
MAILER_DSN=smtp://localhost:1025          # Mailpit en local
MAILER_FROM=noreply@aurora-client.local
ADMIN_EMAIL=admin@aurora-client.local
APP_NAME=aurora-client
```

---

## 4. Base de données

### Option A — Docker (recommandé)

```bash
make docker-up     # démarre PostgreSQL en conteneur
make migrate       # crée les tables
make fixtures      # charge les données de dev
```

### Option B — PostgreSQL local

Crée la base manuellement, puis :

```bash
make migrate
make fixtures
```

---

## 5. Démarrer le serveur de développement

```bash
make start
```

Démarre en parallèle :
- Le serveur Symfony (`symfony server:start`)
- Vite (`pnpm --dir=vendor/axelraboit/aurora dev`)

L'application est accessible sur `https://localhost:8000` (ou le port affiché).

Pour démarrer sans TLS (HTTPS) :

```bash
make start-no-tls
```

---

## 6. Compte administrateur par défaut

Les fixtures créent un compte dev :

| Champ | Valeur |
|---|---|
| Email | `admin@aurora-client.local` (ou valeur de `ADMIN_EMAIL`) |
| Mot de passe | défini dans `DataFixtures/` |
| Rôle | `ROLE_DEV` — accès complet, bypass de tous les privilege checks |

---

## 7. Vérifier que tout fonctionne

```bash
make ft   # fix (linters) + tests (PHP + JS)
```

Les tests tournent contre la base de test (`aurora_client_test`) créée
automatiquement par `make db-test`. Tous les tests doivent passer en vert.

---

## 8. Variables d'environnement complètes

| Variable | Défaut | Rôle |
|---|---|---|
| `APP_ENV` | `dev` | Environnement Symfony |
| `APP_SECRET` | *(à définir)* | Clé de chiffrement sessions/CSRF |
| `DATABASE_URL` | `postgresql://…/aurora-client` | Connexion PostgreSQL |
| `MESSENGER_TRANSPORT_DSN` | `doctrine://default?auto_setup=0` | Transport async |
| `MAILER_DSN` | `smtp://localhost:1025` | Envoi d'emails |
| `MAILER_FROM` | `noreply@aurora-client.local` | Expéditeur des emails |
| `ADMIN_EMAIL` | `admin@aurora-client.local` | Email du compte admin par défaut |
| `APP_NAME` | `aurora-client` | Nom de l'application (affiché dans l'UI) |
| `APP_SHARE_DIR` | `var/share` | Dossier partagé entre workers |
| `DEFAULT_URI` | `http://localhost` | URI de base pour les emails |
| `OCR_DOCTR_URL` | *(optionnel)* | URL du microservice docTR (module Billing/OCR) |
| `OLLAMA_URL` | *(optionnel)* | URL Ollama pour l'extraction OCR |
| `OLLAMA_VISION_MODEL` | `qwen2.5vl:3b` | Modèle vision Ollama |

---

## Démarrer un nouveau projet

Aurora-client est le point de départ pour tout nouveau projet. Une fois
`make install-dev` fait, supprime le code showcase :

```bash
make init-project
```

Cette commande :
- Supprime le module Tracking, l'extension Agency, les overrides Vue et les templates showcase
- Restaure des configs propres (`services.yaml`, `doctrine.yaml`, `twig.yaml`)
- Supprime les migrations showcase et recrée la base depuis zéro
- Resynchronise les paramètres, privileges et menus Aurora

Après `make init-project`, ton projet est vierge — prêt à recevoir ton propre code.

**Étapes suivantes recommandées :**
1. Mettre à jour `composer.json` (name, description)
2. Mettre à jour `.env` (`APP_NAME`, `DATABASE_URL`)
3. `git commit -m "chore: init project from aurora-client template"`
