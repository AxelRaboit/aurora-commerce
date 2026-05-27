<div align="center">

# Aurora

**CMS headless moderne avec éditeur bloc multi-langue**

[![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?style=flat-square&logo=symfony&logoColor=white)](https://symfony.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=flat-square&logo=vue.js&logoColor=white)](https://vuejs.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4-38BDF8?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Vite](https://img.shields.io/badge/Vite-8-646CFF?style=flat-square&logo=vite&logoColor=white)](https://vitejs.dev)

</div>

---

## Présentation

Aurora est un CMS administrable conçu autour d'un éditeur bloc (Editor.js) et d'un modèle de contenu flexible. Chaque contenu possède plusieurs traductions indépendantes, un type personnalisable, des tags et un statut de publication.

L'administration est une SPA Vue intégrée à Symfony via `symfony/ux-vue`. Le stockage utilise PostgreSQL, l'internationalisation repose sur `vue-i18n`, et l'édition concurrente est gérée par un système de verrouillage optimiste avec résolution de conflits 3-way façon Git.

---

## Fonctionnalités

- **Éditeur bloc Editor.js** — titres, listes, images, tableaux, code, citations, intégrations (YouTube, Vimeo…), plus trois blocs custom : Callout, MediaText et Two Columns
- **Templates de démarrage** — 12 modèles prêts à l'emploi regroupés en catégories (Article, Marketing, Mise en page, Technique) applicables en un clic
- **Multi-langue** — traductions indépendantes par locale (fr, en, es, de) avec champ `slug` verrouillé/déverrouillé par traduction
- **Types de contenu dynamiques** — définir des types de post (Article, Page, etc.) depuis l'admin
- **Tags, médias vedettes, SEO** — méta-titre et méta-description comptés en temps réel
- **Optimistic locking** — deux admins peuvent éditer le même contenu ; le second est prévenu d'un conflit lors de sa sauvegarde et peut fusionner les modifications
- **Résolution de conflits 3-way** — comparaison bloc par bloc entre base / local / remote avec acceptation manuelle par bloc ou en batch (inspiré de Git merge)
- **Rôles** — utilisateurs et développeurs avec impersonification depuis l'admin
- **Demandes d'accès** — visiteurs peuvent demander l'accès, l'admin approuve ou refuse par e-mail
- **Invitations** — envoi d'invitations par e-mail avec message et identifiants optionnels
- **Thème** — mode sombre et mode clair
- **Prévisualisation** — rendu fidèle du contenu avant publication

---

## Résolution de conflits 3-way

Quand deux administrateurs modifient simultanément le même contenu, le second voit ses modifications bloquées à la sauvegarde. Aurora propose alors trois actions :

1. **Voir la version actuelle** — aperçu de la version en base sans perdre son travail local
2. **Fusionner** — ouvre un merge editor plein écran qui diffe les blocs Editor.js entre l'état de base (au chargement), local (en cours) et remote (en base), classifie chaque bloc (`unchanged`, `local-modified`, `remote-modified`, `local-added`, `remote-added`, `conflict`…) et permet de choisir version par version
3. **Forcer ma sauvegarde** — écrase la version en base (Doctrine incrémente quand même le `@Version` pour bloquer les futures sauvegardes conflictuelles)

Le verrouillage optimiste utilise la colonne `#[ORM\Version]` de Doctrine combinée à `EntityManager::lock()` pour détecter les conflits de manière atomique.

---

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Backend | Symfony 7.4, PHP 8.4+ |
| Base de données | PostgreSQL |
| Frontend | Vue 3, Vue i18n, Editor.js |
| Style | Tailwind CSS 4 |
| Emails | Symfony Mailer (SMTP) |
| Build | Vite 8 |
| Tests | Vitest, PHPUnit, Playwright |

---

## Installation

### Prérequis

**Obligatoires**

| Outil | Version | Notes |
|-------|---------|-------|
| PHP | 8.4+ | Extensions : `pdo_pgsql`, `intl`, `ctype`, `iconv` |
| PostgreSQL | 14+ | Séquences natives utilisées par Doctrine |
| Node.js | 20+ | |
| Composer | 2+ | |
| pnpm | 9+ | |
| Docker + docker compose | v2+ | Mailpit (SMTP dev) — et docTR si module OCR Billing activé |

**Binaires système optionnels**

| Binaire | Module | Usage | Installation |
|---------|--------|-------|-------------|
| `pdftk` | **PDF Forms** | Détection des champs AcroForm (`dump_data_fields`) | `sudo apt install pdftk` (ou `pdftk-java` sur Ubuntu 22+) |
| `node` (Node.js ≥ 18) | **PDF Forms** | Remplissage et aplatissement Unicode-safe via `tools/pdf/fill.mjs` (basé sur `pdf-lib`, installé via `pnpm install`) | Déjà requis pour le build assets — aucune install supplémentaire |
| `ssh` (OpenSSH client) | **MountPoint** | Tunnels SSH vers des bases de données distantes | Inclus par défaut sur Linux/macOS |
| `ollama` | **Billing OCR** + **Assistant IA** | Inférence locale (modèle vision OCR + chat assistant + vision assistant) | [ollama.ai](https://ollama.ai) — voir [deployment/ocr_setup.md](docs/aurora-client/deployment/ocr_setup.md) |

> Les modules dont la dépendance est absente se dégradent proprement : PDF Forms crée les documents en statut *Brouillon*, MountPoint affiche une erreur de connexion, OCR met les jobs en erreur avec un message explicite.

> 📋 **Liste exhaustive** des prérequis (système, PHP exts, binaires CLI, services externes, modèles Ollama, vars d'env, spécificités prod) :
> [`docs/aurora-core/ops/prerequisites.md`](docs/aurora-core/ops/prerequisites.md) — à consulter avant chaque install/déploiement.

### Services externes (OCR Billing + Assistant IA)

Deux modules utilisent un Ollama local — **optionnels** si tu ne les actives pas :

| Module | Service | Modèle par défaut | Var .env |
|--------|---------|-------------------|----------|
| **Billing OCR** | docTR (Docker) + Ollama vision (JSON structuré) | `qwen2.5vl:3b` | `OLLAMA_URL`, `OLLAMA_VISION_MODEL` |
| **Assistant IA** | Ollama chat (tool-calling) | `qwen3:8b` | `ASSISTANT_OLLAMA_URL`, `ASSISTANT_CHAT_MODEL` |
| **Assistant IA** | Ollama vision (image_read tool, prose) | `qwen2.5vl:3b` | `ASSISTANT_VISION_MODEL` |

```bash
# Installer Ollama
curl -fsSL https://ollama.ai/install.sh | sh

# Modèle Billing OCR (réutilisé par l'assistant image_read)
ollama pull qwen2.5vl:3b

# Modèle Assistant IA (chat avec tool calling — doit être tools-aware)
ollama pull qwen3:8b

# Lancer docTR si tu utilises l'OCR (Docker requis)
make docker-up
```

⚠ Le modèle de chat **doit supporter le tool calling** : `qwen3:*`, `qwen2.5:*`, `llama3.1:*`, `mistral-nemo` OK ; `gemma`, `phi3` non.

Tunables sans redéploiement via [`/backend/configuration/settings`](http://localhost:8000/backend/configuration/settings) → onglet **Assistant** : modèle chat, modèle vision, timeout, num_ctx, prompt système.

→ Documentation complète OCR : [docs/aurora-client/deployment/ocr_setup.md](docs/aurora-client/deployment/ocr_setup.md)

### Mise en place

```bash
git clone https://github.com/axelraboit/aurora.git
cd aurora

make install-dev
```

`make install-dev` installe les dépendances Composer (app + outils), pnpm, crée les répertoires runtime et exécute les migrations.

Copier et configurer l'environnement :

```bash
cp .env .env.local
```

Variables minimales à renseigner dans `.env.local` :

```dotenv
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/aurora"
MAILER_DSN="smtp://localhost:25"
APP_SECRET=your-secret-here
```

Charger des données de démonstration (optionnel — recrée la base entièrement) :

```bash
make fixtures
```

### Développement

```bash
make start              # serveur Symfony + Vite HMR
```

### Production

```bash
make install-prod       # dépendances, migrations, build assets
```

Pour les déploiements suivants (nécessite un tag git sur le commit courant) :

```bash
make deploy-prod
```

---

## Tests

Aurora est testé à trois niveaux :

```bash
make test-frontend             # Vitest — composables et composants Vue
make test-backend-unit         # PHPUnit — tests unitaires
make test-backend-integration  # PHPUnit — tests d'intégration (contrôleurs)
make test-e2e                  # Playwright — end-to-end
make test                      # tout lancer (frontend + backend)
```

### Playwright (E2E) — prérequis WSL/Linux

Les navigateurs Playwright ont besoin de quelques bibliothèques système :

```bash
sudo apt install -y libnspr4 libnss3 libasound2t64
pnpm exec playwright install chromium
```

Le serveur Symfony est démarré automatiquement par Playwright (`symfony server:start --port=8000`). Pour cibler une URL existante à la place, définir `E2E_BASE_URL` :

```bash
E2E_BASE_URL=http://localhost:8000 pnpm test:e2e
```

Le scénario complet de conflit à deux onglets est désactivé par défaut (configuration de fixtures requise) ; l'activer avec :

```bash
E2E_FULL=1 pnpm test:e2e
```

---

## Commandes utiles

```bash
# Développement
make start              # serveur Symfony + Vite HMR
make stop               # arrêter les services Docker

# Tests
make test-backend             # tous les tests backend (PHPUnit)
make test-backend-unit        # tests unitaires backend uniquement
make test-backend-integration # tests d'intégration backend uniquement
make test-frontend            # tests frontend (Vitest)
make test-e2e                 # tests end-to-end (Playwright)
make test                     # frontend + backend

# Qualité du code
make fix               # auto-correction (Rector, PHP-CS-Fixer, ESLint) + PHPStan
make stan              # PHPStan seul

# Base de données
make migrate           # exécuter les migrations
make migration         # générer une nouvelle migration
make fixtures          # drop DB + migrations + fixtures

# Utilitaires
make help              # lister toutes les commandes disponibles
```

---

## Licence

MIT
