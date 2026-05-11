---
name: Architecture ModuleParameterEnum
description: Enum dédié aux paramètres de modules (séparé d'ApplicationParameterEnum) — 13 top-level + 24 sous-modules, cascade graph, liste des consommateurs
type: project
---

## Règle

Les paramètres "module on/off" vivent dans `Aurora\Core\Setting\Enum\ModuleParameterEnum`,
**séparément** de `ApplicationParameterEnum` qui gère les paramètres applicatifs
(SEO, séquences, seuils, etc.).

`ModuleParameterEnum` implémente `ApplicationParameterEnumInterface`.

**Why:** séparation claire entre configuration applicative (onglet Parameters) et
activation de modules (onglet Modules). Le groupe `'modules'` est filtré hors de l'onglet
Parameters (`SettingRepository::findPaginated` l'exclut par défaut).

## Structure

- **13 top-level cases** : un par module. Valeurs string sans `_enabled`
  (ex: `modules_crm_backend`). Incluant `EcommerceFrontend = 'modules_ecommerce_frontend'`
  et `PhotoFrontend = 'modules_photo_frontend'` (variantes front).
- **24 sous-modules** : un par feature. Valeurs sans `_enabled`
  (ex: `backend_crm_contacts`). Billing ×3, CRM ×3, Ecommerce ×2, Editorial ×7,
  GED ×2, ERP ×1, HR ×1, Photo ×1, Planning ×1, Project ×1, Vault ×2.
- `public const MODULE = 'modules'` — identifiant du groupe, utilisé partout à la place
  de la string `'modules'`.
- `getGroup()` → `self::MODULE` pour tous les cases.
- `getParentCase(): ?self` → parent structurel pour les sous-modules (pour UI + cascade disable).
- `getCascadeRequires(): ?string` → clé string du prérequis (inter-module ET intra-module).
- `getCascadeDisableTargets(): array` → récursif via `getCascadeRequires()` ET `getParentCase()`.
- `getModuleId(): ?string` → ID pour les 11 modules principaux (résolution navItems), null pour les autres.

## Dépendances inter-module (top-level)

```
EditorialBackend   — indépendant
CrmBackend         — indépendant
ErpBackend         — requires CrmBackend
EcommerceEnabled   — requires ErpBackend
EcommerceFrontend — requires ErpBackend
BillingEnabled     — requires CrmBackend
PhotoBackend       — indépendant
PhotoFrontend — requires PhotoBackend
GedEnabled         — indépendant
ProjectEnabled     — indépendant
PlanningEnabled    — indépendant
HrEnabled          — indépendant
VaultEnabled       — indépendant ("Module Outils" — coffre-fort + générateur MdP)
```

## Dépendances intra-module (sous-modules)

- Billing : Invoices → Tiers → BillingEnabled ; Compliance → BillingEnabled
- CRM : Deals → Contacts → CrmBackend ; Companies → CrmBackend
- Ecommerce : Orders → Listings → EcommerceEnabled
- Editorial : Taxonomies → PostTypes → EditorialBackend ; Comments, Sitemap → Posts → EditorialBackend
- Tous les autres sous-modules → leur parent directement

## Consommateurs clés

| Fichier | Usage |
|---------|-------|
| `SettingsService` | cascade via `tryFrom($key)` |
| `ModulesController` | validation clé module |
| `ModulesViewBuilder` | payload hiérarchique (parents + subModules) |
| `ApplicationParameterCommand` | sync BDD — fusionne les deux enums |
| `UsersViewBuilder` | moduleToggles par `getModuleId()` |
| `SettingRepository::findPaginated` | exclut groupe 'modules' par défaut |
| `*Context.php` (11 fichiers) | `isBackendEnabled()` + méthodes sous-modules |
| `*Module.php` (11 fichiers) | `getNavSections()` filtre par context |

## How to apply

- Ajouter un module → case top-level dans `ModuleParameterEnum`, `getLabel`, `getDescription`,
  `getDefaultValue` ('1'), `getType` ('bool'), `getGroup` (self::MODULE), `getModuleId` si applicable.
- Ajouter un sous-module → case + `getParentCase()` + `getCascadeRequires()`, puis méthode
  dans le Context + `getNavSections()` dans le Module. Toujours sans `_enabled` dans la valeur string.
- Après tout ajout : `make sync-params` pour créer les entrées DB.
- `ApplicationParameterEnum` ne doit jamais contenir de cases module.
