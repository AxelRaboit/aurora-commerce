# Mettre à jour aurora-core

## Commande

```bash
make aurora-update
```

---

## Ce que fait `make aurora-update`

La commande enchaîne automatiquement :

| Étape | Commande | Rôle |
|---|---|---|
| 1 | `composer update axelraboit/aurora` | Récupère la dernière version d'aurora-core depuis GitHub |
| 2 | `composer install --working-dir=vendor/axelraboit/aurora` | Installe les dépendances PHP du bundle |
| 3 | `pnpm --dir=vendor/axelraboit/aurora install` | Installe les dépendances JS du bundle |
| 4 | `pnpm install` | Met à jour les deps JS du projet client |
| 5 | `make migrate` | Joue les nouvelles migrations Aurora + client |
| 6 | `make sf CMD="aurora:privileges:sync"` | Synchronise les permissions des modules |
| 7 | `make sync-jsconfig` | Régénère `jsconfig.json` (aliases Vite) |
| 8 | `make sync-security` | Synchronise `config/packages/security.yaml` depuis Aurora |
| 9 | `make sync-claude-md` | Met à jour `CLAUDE.md` + recrée les symlinks `.claude/memory/` |
| 10 | `make sync-makefile` | Met à jour le `Makefile` si Aurora en a une nouvelle version |

---

## Fréquence

Lancer `make aurora-update` :
- Après chaque push sur la branche `develop` d'aurora-core
- Avant de démarrer un chantier qui touche des entités ou des conventions Aurora
- En cas de comportement inattendu (pour s'assurer d'avoir la dernière version)

---

## Vérifier après une mise à jour

```bash
make ft             # tests verts + linters
make schema-validate  # le schéma Doctrine est cohérent
```

Si des migrations ont été ajoutées dans aurora-core, elles sont jouées
automatiquement à l'étape 5. Vérifier que la migration n'a pas de conflit
avec les migrations client existantes.

---

## Breaking changes

Les breaking changes sont listés dans le `CHANGELOG.md` d'aurora-core
sous une ligne préfixée `BREAKING:`.

Avant une mise à jour importante :

```bash
cat vendor/axelraboit/aurora/CHANGELOG.md | head -50
```

---

## Cas particulier : Makefile mis à jour

Si `make sync-makefile` détecte que le Makefile a changé, il l'écrase et
affiche :

```
✅ Makefile updated from aurora-core — re-run 'make aurora-update' if needed
```

Dans ce cas, **relancer `make aurora-update`** pour que les nouvelles targets
soient disponibles dès la suite de la séquence.

---

## Customisations préservées au sync

| Fichier | Comportement |
|---|---|
| `CLAUDE.md` | **Écrasé** — ne pas éditer, créer `CLAUDE.local.md` à la place |
| `Makefile` | **Écrasé** — targets custom dans `Makefile.local` (jamais touché) |
| `config/packages/security.yaml` | **Écrasé** — géré par Aurora |
| `.claude/memory/aurora-core/` | Symlink vers vendor — automatiquement à jour |
| `.claude/memory/aurora-client/` | Symlink vers vendor — automatiquement à jour |
| `docs/aurora-core/` | Symlink vers vendor — automatiquement à jour |
| `docs/aurora-client/` | Symlink vers vendor — automatiquement à jour |
| `src/`, `templates/`, `assets/client/` | **Jamais touchés** |
| `.env.local` | **Jamais touché** |
| `Makefile.local` | **Jamais touché** |
| `CLAUDE.local.md` | **Jamais touché** |
