# aurora-client

> 🔗 **Fichier symlinké** vers `vendor/axelraboit/aurora/.claude/client_template/README.md`.
> Toujours à jour avec la version installée d'aurora-core — aucun sync manuel
> requis. Pour ajouter du contenu **spécifique au projet client** (URL de
> staging, particularités métier, logo, etc.), créer `README.local.md` à
> côté de ce fichier — il sera lu en parallèle par les nouveaux arrivants.

A client application built on [Aurora](https://github.com/AxelRaboit/aurora-core).
Aurora is installed as a Composer dependency at `vendor/axelraboit/aurora/`
and provides the full backend (CRUD, auth, modules, Vue admin SPA, etc.).

## Setup initial

```bash
cp .env.local.example .env.local       # fill in your DB credentials + AURORA_*_KEY
make install-dev                        # composer + pnpm + DB create + migrate + fixtures + dev server
```

**Identifiants par défaut** : `admin@aurora.app` / `password`

## Le quotidien

| Situation | Commande | Effet |
|---|---|---|
| Pull la PR d'un collègue | `make pull-update` | Sync deps + migrations + cache (préserve la BDD) |
| Bump volontaire d'aurora-core | `make aurora-update` | `composer update axelraboit/aurora` + sub-installs + syncs |
| Lancer le dev en local | `make start` ou `make dev` | PHP server + Vite watcher |
| Tests + linters | `make ft` | `make fix` + `make test` |
| Recharger les fixtures démo | `make demo` | `doctrine:fixtures:load --group=demo` |

⚠️ **Ne JAMAIS faire `make install-dev` sur un projet déjà setup** — il purge
la BDD via `doctrine:fixtures:load`. Les données locales sont écrasées.

## Comment utiliser Aurora ?

Toutes les conventions client-side (où mettre le code, comment override
les services / templates / composants Vue Aurora, comment étendre une
entité, …) vivent dans la doc d'Aurora pour rester en phase avec la
version installée :

| Sujet | Chemin |
|---|---|
| **Quickstart** — *où mettre mon code ?* | `vendor/axelraboit/aurora/docs/aurora-client/getting_started/setup.md` |
| Architecture du projet | `vendor/axelraboit/aurora/docs/aurora-client/getting_started/architecture.md` |
| Étendre une entité (5 couches) | `vendor/axelraboit/aurora/docs/aurora-client/extending/extend_entity.md` |
| Créer un module client complet | `vendor/axelraboit/aurora/docs/aurora-client/extending/add_module.md` |
| Workflow dev quotidien | `vendor/axelraboit/aurora/docs/aurora-client/dev/dev_workflow.md` |
| Mises à jour Aurora (détaillé) | `vendor/axelraboit/aurora/docs/aurora-client/dev/update_aurora.md` |
| Conventions globales (Vue, fetch, JS, i18n, commits) | `vendor/axelraboit/aurora/.claude/memory/aurora-shared/MEMORY.md` |

Pour les utilisateurs de Claude Code, [`CLAUDE.md`](CLAUDE.md) (lui aussi
symlinké) indexe tout ça et est chargé automatiquement au démarrage de
chaque session.

## Customisation projet

Trois fichiers permettent d'ajouter du contenu spécifique au projet client
qui ne sera **jamais écrasé** par `make aurora-update` ou `make pull-update` :

- **`README.local.md`** — section ajoutée au README pour les nouveaux arrivants
  (URL staging, particularités métier, contacts équipe)
- **`CLAUDE.local.md`** — instructions Claude spécifiques au projet (conventions
  internes, intégrations tierces)
- **`Makefile.local`** — targets Makefile custom (déploiement, intégrations
  CI/CD spécifiques)

Ces fichiers sont gitignorés/listés dans `.gitignore` côté client — vérifier
selon vos pratiques d'équipe si vous voulez les commit ou non.
