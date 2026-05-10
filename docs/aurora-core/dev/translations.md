# Traductions — architecture et conventions

## Vue d'ensemble

Aurora utilise **une seule source de vérité** : les fichiers YAML. Le JSON
frontEnd est un artefact de build régénéré à partir des YAML.

```
YAML sources  ──make i18n──►  assets/locales/generated/{fr,en}.json  ──build──►  Vue i18n
     │
     └──  Symfony Translator (Twig, PHP, console)
```

---

## Structure des fichiers YAML

### Modules métier

Chaque module expose ses propres traductions dans :

```
src/Module/<Module>/translations/
├── messages.fr.yaml
└── messages.en.yaml
```

Exemple — Billing :
```
src/Module/Billing/translations/messages.fr.yaml   → billing.*, backend.billing.*, backend.nav.invoices, ...
```

### Core — découpé par feature

Le Core suit le même principe : chaque feature a son propre dossier `translations/`.

```
src/Core/
├── Auth/translations/messages.{fr,en}.yaml         → backend.auth, frontend.login/register/…, shared.password
├── Audit/translations/messages.{fr,en}.yaml        → backend.audit
├── Mail/translations/messages.{fr,en}.yaml         → frontend.mail, shared.mail
├── Media/translations/messages.{fr,en}.yaml        → backend.media, shared.media, shared.dropZone
├── Menu/translations/messages.{fr,en}.yaml         → backend.menus, backend.nav, frontend.menu
├── Module/translations/messages.{fr,en}.yaml       → backend.permissions, backend.modules
├── MountPoint/translations/messages.{fr,en}.yaml   → backend.mountPoints
├── Notification/translations/messages.{fr,en}.yaml → backend.notifications
├── Profile/translations/messages.{fr,en}.yaml      → backend.profile, backend.impersonation
├── Search/translations/messages.{fr,en}.yaml       → backend.search
├── Setting/translations/messages.{fr,en}.yaml      → backend.settings, backend.parameters, backend.tabs, backend.stats
├── Theme/translations/messages.{fr,en}.yaml        → backend.themes, frontend.theme
├── User/translations/messages.{fr,en}.yaml         → backend.users, backend.roles, backend.invitations, backend.access_requests
└── translations/                                   → clés vraiment transversales + security + validators
    ├── messages.{fr,en}.yaml                       → shared.common, shared.locales, shared.pagination, shared.form, shared.comment
    ├── security.{fr,en,es,de}.yaml
    └── validators.{fr,en,es,de}.yaml
```

### Découverte automatique

`AuroraBundle` et `DumpJsTranslationsCommand` scannent automatiquement
**par glob** :
- `src/Core/translations/`
- `src/Core/*/translations/`
- `src/Module/*/translations/`

Ajouter un nouveau dossier `Feature/translations/` dans Core suffit — aucune
configuration manuelle requise.

---

## Workflow : ajouter ou modifier une traduction

```bash
# 1. Identifier le bon fichier YAML
#    - Clé backend.billing.* → src/Module/Billing/translations/messages.fr.yaml
#    - Clé backend.media.*   → src/Core/Media/translations/messages.fr.yaml
#    - Clé shared.common.*   → src/Core/translations/messages.fr.yaml

# 2. Éditer FR ET EN
vim src/Core/Media/translations/messages.fr.yaml
vim src/Core/Media/translations/messages.en.yaml

# 3. Régénérer le JSON frontend
make i18n

# 4. Vérifier (optionnel)
php bin/console debug:translation fr --domain=messages | grep ma.cle
```

**Ne jamais** toucher `assets/locales/generated/*.json` directement — tout
changement sera écrasé par le prochain `make i18n`.

---

## Créer les traductions d'un nouveau module

Créer un dossier `translations/` dans le module suffit :

```
src/Module/MonModule/translations/
├── messages.fr.yaml
└── messages.en.yaml
```

Convention de structure dans le YAML :

```yaml
# messages.fr.yaml
backend:
  modules:
    mon_module: Mon module          # label dans la page Modules
    mon_module_description: ...     # description dans la page Modules
  nav:
    sections:
      mon_module: Mon module        # titre de section dans la sidebar
    mon_entite: Mes entités         # label du lien nav
    mon_entite_description: ...     # tooltip hover de la sidebar
  permissions:
    names:
      mon_module:
        mon_entite:
          view: Voir les entités
          create: Créer
          edit: Modifier
          delete: Supprimer
  audit:
    actions:
      mon_module:
        mon_entite:
          created: Entité créée
          updated: Entité modifiée
          deleted: Entité supprimée
  mon_module:                       # traductions spécifiques au module
    add: Ajouter
    empty: Aucune entité.
    # ...
```

---

## Conventions de nommage des clés

| Préfixe | Usage |
|---------|-------|
| `backend.nav.*` | Labels et descriptions des items de navigation (sidebar) |
| `backend.nav.sections.*` | Titres de sections de la sidebar |
| `backend.modules.*` | Labels des modules dans la page `/dev/dashboard/modules` |
| `backend.permissions.names.*` | Labels des privilèges dans la page Permissions |
| `backend.audit.actions.*` | Labels des actions dans l'onglet Audit |
| `backend.<module>.*` | Traductions UI spécifiques au module (formulaires, messages, etc.) |
| `shared.common.*` | Actions génériques universelles (save, cancel, delete…) |
| `shared.locales.*` | Noms de langues (fr → Français) |

---

## Tests de cohérence

Un test PHPUnit vérifie automatiquement à chaque `make ft` :

```
tests/Unit/Translation/TranslationConsistencyTest.php
```

Il couvre **toutes les paires FR/EN** des 13 features Core + 12 modules et
valide :

1. **Parité des clés** — toute clé FR doit exister en EN et vice versa
2. **Pas de valeurs vides** — aucune clé sans traduction
3. **Cohérence des placeholders** — `{name}` en FR = `{name}` en EN

Si une violation est détectée, le test échoue avec le nom de la clé manquante.
