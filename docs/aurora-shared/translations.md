# Traductions — architecture et conventions

## Vue d'ensemble

Aurora utilise **une seule source de vérité** : les fichiers YAML. Le JSON
frontEnd est un artefact de build régénéré à partir des YAML.

```
YAML sources  ──make translation──►  assets/locales/generated/{fr,en}.json  ──build──►  Vue i18n
     │
     └──  Symfony Translator (Twig, PHP, console)
```

> **Locales actives** : `fr` et `en` uniquement (cf. `Aurora\Core\Locale\Enum\LocaleEnum`).
> Des fichiers `security.{es,de}.yaml` / `validators.{es,de}.yaml` existent
> historiquement dans `src/Core/translations/` mais **ne sont pas générés en
> JSON frontend** tant que `LocaleEnum` n'a pas les cases correspondantes.
> Ajouter une locale = code change centralisé dans `LocaleEnum` côté core
> (cf. mémoire `decision_locale_added_in_core.md`).

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

### Core — pure infrastructure (depuis 0.4.0)

`src/Core/` n'héberge plus de modules (Platform, Configuration, etc. sont
sous `src/Module/`). Il contient uniquement de l'infrastructure cross-cutting
qui a éventuellement quelques YAML :

```
src/Core/
├── Mail/translations/messages.{fr,en}.yaml         → frontend.mail, shared.mail
├── Notification/translations/messages.{fr,en}.yaml → backend.notifications
└── translations/                                   → clés transversales + security + validators
    ├── messages.{fr,en}.yaml                       → shared.common, shared.locales, shared.pagination, shared.form, shared.comment
    ├── security.{fr,en,es,de}.yaml
    └── validators.{fr,en,es,de}.yaml
```

### Modules — chacun avec ses translations

Tous les modules (Core promus + business) vivent sous `src/Module/<X>/` et
ont leur dossier `translations/`. Sous-modules pareil (depth 2).

```
src/Module/
├── Platform/User/translations/messages.{fr,en}.yaml         → backend.users, backend.roles, backend.invitations, backend.access_requests
├── Platform/Auth/translations/messages.{fr,en}.yaml         → backend.auth, frontend.login/register/…, shared.password
├── Configuration/Setting/translations/messages.{fr,en}.yaml → backend.settings, backend.parameters, …
├── Configuration/Theme/translations/messages.{fr,en}.yaml   → backend.themes, frontend.theme
├── Media/Library/translations/messages.{fr,en}.yaml         → backend.media, shared.media, shared.dropZone
├── General/Dashboard/translations/messages.{fr,en}.yaml     → backend.dashboard
├── General/Profile/translations/messages.{fr,en}.yaml       → backend.profile, backend.impersonation
├── General/Search/translations/messages.{fr,en}.yaml        → backend.search
├── Dev/Audit/translations/messages.{fr,en}.yaml             → backend.audit
├── Dev/MountPoint/translations/messages.{fr,en}.yaml        → backend.mountPoints
├── Editorial/Menu/translations/messages.{fr,en}.yaml        → backend.menus, backend.nav, frontend.menu
├── Editorial/translations/messages.{fr,en}.yaml             → backend.posts, ...
├── Vault/translations/messages.{fr,en}.yaml                 → backend.vault, ...
└── ... (tous les autres modules métier)
```

### Découverte automatique

`AuroraBundle` et `DumpJsTranslationsCommand` scannent automatiquement
**par glob** (depth 1 ET depth 2 depuis 0.4.0) :
- `src/Core/translations/`
- `src/Core/*/translations/` (Mail, Notification)
- `src/Module/*/translations/` (modules au top-level)
- `src/Module/*/*/translations/` (sous-modules nichés)

Ajouter un nouveau dossier `translations/` à un module ou sous-module
suffit — aucune configuration manuelle requise.

---

## Workflow : ajouter ou modifier une traduction

```bash
# 1. Identifier le bon fichier YAML
#    - Clé backend.billing.* → src/Module/Billing/translations/messages.fr.yaml
#    - Clé backend.media.*   → src/Module/Media/translations/messages.fr.yaml
#    - Clé shared.common.*   → src/Core/translations/messages.fr.yaml

# 2. Éditer FR ET EN
vim src/Module/Media/translations/messages.fr.yaml
vim src/Module/Media/translations/messages.en.yaml

# 3. Régénérer le JSON frontend
make translation

# 4. Vérifier (optionnel)
php bin/console debug:translation fr --domain=messages | grep ma.cle
```

**Ne jamais** toucher `assets/locales/generated/*.json` directement — tout
changement sera écrasé par le prochain `make translation`.

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
      mon_module: Mon module        # titre de section dans la sidemenu
    mon_entite: Mes entités         # label du lien nav
    mon_entite_description: ...     # tooltip hover de la sidemenu
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
| `backend.nav.*` | Labels et descriptions des items de navigation (sidemenu) |
| `backend.nav.sections.*` | Titres de sections de la sidemenu |
| `backend.modules.*` | Labels des modules dans la page `/dev/dashboard/modules` |
| `backend.permissions.names.*` | Labels des privilèges dans la page Permissions |
| `backend.audit.actions.*` | Labels des actions dans l'onglet Audit |
| `backend.<module>.*` | Traductions UI spécifiques au module (formulaires, messages, etc.) |
| `shared.common.*` | Actions génériques universelles (save, cancel, delete…) |
| `shared.locales.*` | Noms de langues (fr → Français) |

---

## Casse des clés — deux styles coexistent (intentionnel)

Le projet utilise **deux styles de casse selon l'origine de la clé** :

### `snake_case` — clés construites par le code machine

Utilisé quand la clé est construite **programmatiquement** à partir d'une valeur
d'enum ou d'un identifiant système. La casse est imposée par la valeur PHP :

```php
// PdfTemplateStatusEnum::Draft->value === 'draft'
public function getLabelKey(): string
{
    return 'backend.pdfform.templates.status_'.$this->value; // → status_draft
}
```

```yaml
# ✅ snake_case obligatoire ici — suffixe vient de l'enum
pdfform:
  templates:
    status_draft: Brouillon
    status_active: Actif
```

Clés concernées : labels de status (`status_*`), identifiants de nav globaux
(`pdfform_templates`, `ged_categories`), clés de paramètres (`ged_document_prefix`).

### `camelCase` — clés nommées manuellement dans l'UI

Utilisé pour toutes les clés **écrites explicitement** dans le YAML pour les
libellés UI, messages, placeholders :

```yaml
# ✅ camelCase pour les clés UI nommées à la main
pdfform:
  templates:
    searchPlaceholder: Rechercher un template…
    deleteConfirm: "Supprimer le template « {name} » ?"
    fieldCount: Champs
    noFile: Aucun fichier
```

### Règle de décision

> **La clé contient une valeur d'enum ou un identifiant système ?**
> → `snake_case` (contraint par le code)
>
> **La clé est nommée librement pour l'UI ?**
> → `camelCase` (convention projet)

Ne pas chercher à uniformiser : forcer tout en `snake_case` obligerait à
transformer les valeurs d'enum dans `getLabelKey()` (fragile), et forcer tout
en `camelCase` casserait la correspondance directe avec les valeurs d'enum
(`status_draft` → `statusDraft` nécessiterait une transformation).
Le mixte actuel est le seul format sans friction.

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
