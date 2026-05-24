# GitHub Actions CI — setup d'un projet aurora-client

Le template aurora-client embarque un workflow GitHub Actions
(`.github/workflows/ci.yml`) qui exécute, à chaque push sur les branches
long-lived (`master`, `develop`) et sur chaque PR :

- `composer validate` + `composer audit`
- Build des assets Vite via `make build`
- Linters : `make lint-php`, `make lint-twig`, `make lint-js`, `make rector`
- Static analysis : `make stan` (PHPStan)
- Setup DB de test (schema:create from entity metadata + mark all
  migrations applied — workaround multi-namespace, cf. §3 ci-dessous)
- Tests : `make test-frontend` + `make test-backend-unit`

Le workflow utilise un **PostgreSQL 18** en service GitHub Actions, **PHP
8.4**, **Node 24** et **pnpm 10** (matrix à 1 entrée).

---

## 1. Pré-requis CI — Personal Access Token (PAT)

aurora-core est un repo **privé**. Le `GITHUB_TOKEN` auto-fourni par
GitHub Actions sur le repo client n'a **pas** accès à d'autres repos
privés du même propriétaire — c'est une frontière de sécurité par
défaut. Sans config supplémentaire, `composer install` plante à l'étape
de clone de `axelraboit/aurora` avec :

```
fatal: Authentication failed for 'https://github.com/AxelRaboit/aurora-core.git/'
```

**Setup en 2 étapes :**

### Étape 1 — Créer un fine-grained PAT

1. Aller sur https://github.com/settings/personal-access-tokens/new
2. **Token name** : ex. `aurora-core read-only for CI of <projet>`
3. **Expiration** : 90 jours (à renouveler) ou + selon ta tolérance
4. **Repository access** → *Only select repositories* → cocher
   `<owner>/aurora-core`
5. **Repository permissions** :
   - **Contents** → `Read-only`
6. Cliquer *Generate token* et **copier la valeur** immédiatement
   (GitHub ne la ré-affiche plus ensuite).

### Étape 2 — Ajouter le PAT en repo secret

Sur le repo client (ex: `<owner>/welding-app`) :

1. *Settings → Secrets and variables → Actions → New repository secret*
2. **Name** : `AURORA_CORE_READ_TOKEN`
3. **Value** : coller le PAT

Re-run le workflow depuis l'onglet *Actions* — `composer install` doit
maintenant passer.

---

## 2. Renouvellement du PAT

Les fine-grained PATs expirent. Pour renouveler :

1. Créer un nouveau PAT (même config — cf. §1.1)
2. Sur le repo client, *Secrets → Actions → `AURORA_CORE_READ_TOKEN`* →
   *Update*
3. Coller la nouvelle valeur

Pas besoin de redéployer ou de modifier le workflow.

---

## 3. Setup DB de test — pourquoi `schema:create` au lieu de `migrations:migrate`

Le workflow CI initialise la DB de test via :

```yaml
- name: Create schema from entity metadata
  run: php bin/console doctrine:schema:create --env=test

- name: Initialize migration metadata + mark all applied
  run: |
    php bin/console doctrine:migrations:sync-metadata-storage --env=test --no-interaction
    php bin/console doctrine:migrations:version --add --all --no-interaction --env=test
```

**Pourquoi pas `doctrine:migrations:migrate` directement ?**

Aurora-client utilise deux namespaces de migrations :

- `DoctrineMigrations` — celles du vendor `axelraboit/aurora`
- `ClientMigrations` — celles du projet client (sous `migrations/`)

Sur une DB fresh, Doctrine Migrations 3.x **ne mélange pas
strictement par version timestamp** à travers les namespaces — il
traite chaque namespace dans son ordre de déclaration. Quand une
migration `ClientMigrations\Version20260508123924` (par exemple, une
extension de table Aurora) tente d'ALTER une table créée par
`DoctrineMigrations\Version20260508122957` (version timestamp **plus
petite** donc qui devrait passer avant), Doctrine plante car la table
n'existe pas encore.

`schema:create` génère le schéma actuel depuis les annotations
Doctrine des entités (vendor + client), puis on marque toutes les
migrations comme déjà appliquées. Résultat équivalent en moins
d'opérations, et **sans dépendre de l'ordre de migrations**.

> ⚠️ Cette approche fonctionne **uniquement** parce que les migrations
> du projet sont purement structurelles (CREATE / ALTER / RENAME) — pas
> de migrations de données (data backfills). Si vous ajoutez une
> migration qui insère / convertit des données via SQL, le CI
> *raterait* ce backfill. Dans ce cas, exécuter manuellement la
> migration de données dans une étape CI séparée, OU réécrire la
> donnée via les fixtures.

> En **runtime quotidien dev / prod**, `make migrate-f` (qui fait
> `doctrine:migrations:migrate`) marche normalement — vous n'ajoutez
> qu'**une seule** migration neuve à la fois (un namespace), pas un
> mélange historique entier.

---

## 4. Modifier le workflow CI

Le fichier `.github/workflows/ci.yml` est **owned par le projet
client** — pas synchronisé par `make aurora-update`. Libre à toi de :

- Ajouter une matrice (multi-PHP, multi-PostgreSQL)
- Ajouter des steps de déploiement post-tests (push Docker image, deploy
  Kubernetes, etc.)
- Ajouter des Integration tests (`make test-backend-integration`) une
  fois que tu en as
- Désactiver le workflow temporairement (commenter le bloc `on:`)

> 💡 Pour qu'aurora-core (le bundle) puisse fournir des **mises à jour**
> du workflow CI à tous ses clients à l'avenir, on pourrait introduire
> un `sync-github-actions` Make target qui copie `.github/workflows/ci.yml`
> depuis le vendor à chaque `aurora-update`. Pas encore en place — pour
> l'instant chaque client maintient son CI manuellement.

---

## 5. Pièges connus

- **PAT expiré** : la build CI plante avec le même message
  d'authentification que sans PAT du tout. Re-générer (cf. §2).
- **Mauvaise expiration de token** : un PAT *classique* (legacy) doit
  avoir le scope `repo` au minimum. Privilégier les *fine-grained*
  (plus restrictifs, plus auditable).
- **Cache pnpm/composer périmé** : la clé de cache contient le hash
  du lockfile correspondant — un changement de lockfile invalide le
  cache automatiquement. Si tu suspectes un cache corrompu, supprime
  les caches via *Actions → Caches → Delete*.
- **Workflow trigger sur `main` vs `master`** : aurora-client par
  convention utilise `master + develop`. Si ton projet utilise
  `main + develop`, modifier `on.push.branches` en haut du fichier.

---

## 6. Variants — autres CI providers

Le workflow GitHub Actions est le seul fourni dans le template. Pour
GitLab CI / Bitbucket Pipelines / Jenkins, recréer la même séquence
d'étapes :

```
composer install (avec auth vendor aurora-core)
pnpm install (vendor aurora + client root)
make build
make lint-php lint-twig lint-js rector stan
schema:create + migrations:version --add --all
make test-frontend test-backend-unit
```

L'authentification au vendor `axelraboit/aurora` varie par provider —
Deploy Key SSH (GitLab CI) ou variable secrète avec PAT (équivalent
GitHub Actions). Adapter selon ta cible.
