---
name: Convention traductions i18n (YAML sources + structure)
description: Architecture complète des traductions — où écrire, structure par feature, pipeline YAML→JSON, tests de cohérence
type: project
---

## Règle

**Source unique de vérité : les fichiers YAML.** Le JSON frontend est un artefact
de build régénéré par `make translation`. Ne jamais toucher `assets/locales/generated/*.json`.

## Structure des fichiers

### Modules métier
```
src/Module/<Module>/translations/messages.{fr,en}.yaml
```

### Core — découpé par feature (depuis mai 2026)
```
src/Core/Auth/translations/         → backend.auth, frontend.login/register/…, shared.password
src/Core/Audit/translations/        → backend.audit
src/Core/Mail/translations/         → frontend.mail, shared.mail
src/Core/Media/translations/        → backend.media, shared.media, shared.dropZone
src/Core/Menu/translations/         → backend.menus, backend.nav, frontend.menu
src/Core/Module/translations/       → backend.permissions, backend.modules
src/Core/MountPoint/translations/   → backend.mountPoints
src/Core/Notification/translations/ → backend.notifications
src/Core/Profile/translations/      → backend.profile, backend.impersonation
src/Core/Search/translations/       → backend.search
src/Core/Setting/translations/      → backend.settings, backend.parameters, backend.tabs, backend.stats
src/Core/Theme/translations/        → backend.themes, frontend.theme
src/Core/User/translations/         → backend.users, backend.roles, backend.invitations, backend.access_requests
src/Core/translations/              → shared.common, shared.locales, shared.pagination, shared.form, shared.comment + security.* + validators.*
```

**Découverte automatique** via glob dans `AuroraBundle` et `DumpJsTranslationsCommand` :
`src/Core/*/translations/`, `src/Module/*/translations/` — aucune config manuelle.

## Workflow

```bash
# Modifier FR + EN dans le bon fichier YAML, puis :
make translation   # régénère assets/locales/generated/{fr,en}.json
```

## Where does a key go?

- `backend.billing.*` → `src/Module/Billing/translations/`
- `backend.media.*` → `src/Core/Media/translations/`
- `shared.common.*` → `src/Core/translations/messages.{fr,en}.yaml`
- Nouvelle feature Core → créer `src/Core/<Feature>/translations/messages.{fr,en}.yaml`

**Why:** séparation par feature = co-localisation avec le code qui utilise la traduction.
Un dev qui touche `src/Core/Media/` sait exactement où sont ses traductions.

## Tests de cohérence

`tests/Unit/Translation/TranslationConsistencyTest.php` tourne à chaque `make ft` et
valide sur toutes les paires FR/EN (25 au total) :
1. Parité des clés FR↔EN
2. Pas de valeurs vides
3. Cohérence des `{placeholders}`

## How to apply

- Nouveau module : créer `src/Module/<Module>/translations/messages.{fr,en}.yaml`, découvert auto.
- Nouvelle feature Core : créer `src/Core/<Feature>/translations/messages.{fr,en}.yaml`, découvert auto.
- Doc complète : `docs/aurora-shared/translations.md`
