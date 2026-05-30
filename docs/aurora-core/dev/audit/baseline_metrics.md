# Baseline métriques — pré-split (J0)

> Snapshot **2026-05-30**, tag git `pre-monorepo-audit`. Sert de référence
> pour mesurer les régressions à chaque jalon (build time, install size).

| Métrique | Valeur |
|---|---|
| Package Composer | `axelraboit/aurora` (mono) |
| Dépendances `require` | 41 |
| Dépendances `require-dev` | 6 |
| Modules (`src/Module/*`) | 18 |
| Migrations Doctrine | 17 fichiers (1 dossier `migrations/`) |
| `resolve_target_entities` | 95 paires (manuelles, dans `AuroraBundle`) |
| `ModuleParameterEnum` cases | 66 |
| Build Vue (`public/build`) | **9.9 Mo** |
| Total PHP LoC modules | ~92 000 (somme colonne inventory) |
| Tests (suite) | 492+ (cf. CLAUDE.md) |

## Outillage monorepo

- `splitsh-lite` : **non installé** localement → à fournir (binaire ou
  Docker) avant J3/POC.
- Docker : disponible.
- `symplify/monorepo-builder` : non présent dans `composer.json`.

## Rollback

- `git tag pre-monorepo-audit` posé sur `aurora-core`.
- ⚠️ Tag équivalent **pas encore posé sur `aurora-client`** (`../aurora-client`
  est cloné mais non taggé) — à faire avant toute modif client (J2 Track B).
