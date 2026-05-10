---
name: Convention casse des clés de traduction (camelCase vs snake_case)
description: Les clés YAML de traduction utilisent deux styles selon leur origine — snake_case si construites par le code, camelCase si nommées manuellement
type: feedback
---

## Règle

Deux styles coexistent **intentionnellement** dans les YAML de traduction Aurora :

- **`snake_case`** → clés construites par le code à partir d'une valeur d'enum ou d'un identifiant système
- **`camelCase`** → clés nommées manuellement pour les libellés UI

## Pourquoi

Les labels de status sont construits programmatiquement :

```php
return 'backend.pdfform.templates.status_'.$this->value; // value = 'draft'
// → 'backend.pdfform.templates.status_draft'
```

La casse est imposée par la valeur de l'enum PHP (toujours snake_case/lowercase).
Forcer tout en camelCase obligerait une transformation dans `getLabelKey()` (fragile) ;
forcer tout en snake_case irait à l'encontre de la convention UI du projet.

## Comment l'appliquer

| Situation | Style | Exemples |
|---|---|---|
| Valeur d'enum comme suffixe | `snake_case` | `status_draft`, `status_active` |
| Identifiant nav global | `snake_case` | `pdfform_templates`, `ged_categories` |
| Clé de paramètre | `snake_case` | `ged_document_prefix` |
| Libellé UI nommé à la main | `camelCase` | `searchPlaceholder`, `deleteConfirm`, `fieldCount` |

**Règle de décision rapide** : la clé contient-elle une valeur d'enum ou un id système ? → `snake_case`. Sinon → `camelCase`.

Ne jamais chercher à uniformiser en un seul style — le mixte est sans friction.

## Référence

Doc complète : `docs/aurora-core/dev/translations.md` § "Casse des clés"
