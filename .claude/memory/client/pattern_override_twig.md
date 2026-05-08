# Pattern : override Twig automatique

## Règle

Le bundle Aurora prepend automatiquement
`%kernel.project_dir%/templates/<Namespace>/` devant son propre chemin
sous chaque namespace `@<Namespace>`. Un override client met juste son
fichier au bon endroit dans `templates/` — résolu en priorité.

## Pourquoi

Pas besoin de configurer Twig manuellement. Le bundle gère le namespace
prepend à `boot()`. Les clients ont juste à créer le fichier au chemin
miroir de celui d'Aurora.

## Comment l'appliquer

### Override d'un template admin

Le template Aurora vit dans `vendor/axelraboit/aurora/templates/Core/backend/agencies/index.html.twig`
(résolu via `@Core/backend/agencies/index.html.twig`).

Pour l'override côté client :

```bash
mkdir -p templates/Core/backend/agencies
# Créer templates/Core/backend/agencies/index.html.twig
```

Le template client est résolu en priorité dès qu'il existe. Le namespace
Twig `@Core/...` reste le même — c'est juste l'ordre des paths qui change.

### Mappings utiles

| Namespace Twig | Path Aurora | Override client |
|---|---|---|
| `@Core` | `vendor/axelraboit/aurora/templates/Core/` | `templates/Core/` |
| `@Editorial` | `vendor/.../templates/Module/Editorial/` | `templates/Module/Editorial/` |
| `@Crm` | `vendor/.../templates/Module/Crm/` | `templates/Module/Crm/` |
| `@Erp` | `vendor/.../templates/Module/Erp/` | `templates/Module/Erp/` |
| `@Project` | `vendor/.../templates/Module/Project/` | `templates/Module/Project/` |
| `@Photo` | `vendor/.../templates/Module/Photo/` | `templates/Module/Photo/` |
| `@Billing` | `vendor/.../templates/Module/Billing/` | `templates/Module/Billing/` |
| `@Ecommerce` | `vendor/.../templates/Module/Ecommerce/` | `templates/Module/Ecommerce/` |
| `@Ged` | `vendor/.../templates/Module/Ged/` | `templates/Module/Ged/` |
| `@Shared` | `vendor/.../templates/shared/` | `templates/shared/` |

### Étendre plutôt que remplacer

Souvent on veut **enrichir** le template Aurora avec un block, pas le
remplacer entièrement :

```twig
{# templates/Core/backend/agencies/index.html.twig (override client) #}
{% extends '@Core/backend/agencies/index.html.twig' %}

{# Pas du tout possible — on étendrait soi-même, infinite loop. #}
```

❌ Ce pattern ne marche pas (extension récursive). À la place, deux options :

#### Option A : Recopier + modifier

Copier le template Aurora dans le client + modifier ce qu'on veut. Pas
idéal (drift à chaque update Aurora) mais parfois nécessaire pour des
modifications structurelles.

#### Option B : Bloc étendable côté Aurora

Si on a besoin d'un point d'extension récurrent, **demander qu'Aurora
ajoute un block `{% block client_extra %}{% endblock %}`** dans son
template, qu'on peut overrider proprement :

```twig
{# templates/Core/backend/agencies/_extra_client.html.twig (côté client) #}
<div class="custom-stuff">…</div>
```

Et côté Aurora :
```twig
{# Aurora's index.html.twig #}
{% include '@Core/backend/agencies/_extra_client.html.twig' ignore missing %}
```

Le `ignore missing` permet à Aurora de tolérer l'absence d'override.

## Source

Cf section "Couche 5 / 5.3 Override Twig automatique" du doc convention.
Implémenté dans `Aurora\AuroraBundle::boot()` qui itère sur
`templates/<Namespace>/` et prepend dans Twig loader.
