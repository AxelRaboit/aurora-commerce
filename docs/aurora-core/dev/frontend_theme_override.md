# Frontend — Système de thèmes et override de templates

## Principe

Le site public (frontend) utilise un système de thèmes basé sur `ThemeResolver`.
Chaque template frontend est résolu ainsi :

1. Un thème est marqué `active = true` en BDD.
2. `ThemeResolver::resolve('editorial/home')` cherche d'abord
   `templates/Frontend/themes/<slug>/editorial/home.html.twig`.
3. S'il existe → ce fichier est utilisé. Sinon → fallback sur
   `templates/Frontend/themes/default/editorial/home.html.twig`.

Un thème custom n'a donc besoin de ne contenir **que les templates qu'il override**.
Tout ce qui n'est pas présent dans le thème custom tombe sur `default`.

---

## Structure des thèmes

```
templates/Frontend/themes/
  default/                    ← thème de référence livré avec Aurora
    layout.html.twig
    partials/
    auth/
    editorial/
    ecommerce/
    photo/
    ged/
  mon-theme/                  ← thème custom (override partiel)
    layout.html.twig          ← override du layout (nav, footer, head)
    editorial/
      home.html.twig          ← override de la home uniquement
```

---

## Créer un thème custom

### 1. Créer le dossier

```bash
mkdir -p templates/Frontend/themes/mon-theme/
```

### 2. Copier uniquement les templates à modifier

Ne copier que ce qu'on veut changer. Exemple — changer uniquement le layout :

```bash
cp templates/Frontend/themes/default/layout.html.twig \
   templates/Frontend/themes/mon-theme/layout.html.twig
```

Éditer le fichier copié librement.

### 3. Enregistrer le thème en BDD

```bash
php bin/console dbal:run-sql "
  INSERT INTO core_themes (id, slug, name, active, config)
  VALUES (NEXTVAL('seq_core_theme_id'), 'mon-theme', 'Mon Thème', false, '{}')
  ON CONFLICT (slug) DO NOTHING
"
```

### 4. Activer le thème

Un seul thème actif à la fois.

```bash
# Désactiver tous les thèmes, activer le nouveau
php bin/console dbal:run-sql "UPDATE core_themes SET active = false"
php bin/console dbal:run-sql "UPDATE core_themes SET active = true WHERE slug = 'mon-theme'"
```

Revenir au thème par défaut :

```bash
php bin/console dbal:run-sql "UPDATE core_themes SET active = false"
php bin/console dbal:run-sql "UPDATE core_themes SET active = true WHERE slug = 'default'"
```

Alternativement, les thèmes sont gérables depuis le backend admin :
`/backend/themes`.

---

## Templates overridables

Tous les templates sous `Frontend/themes/default/` sont overridables :

| Chemin | Rôle |
|---|---|
| `layout.html.twig` | Layout principal (nav, footer, `<head>`) |
| `partials/head.html.twig` | Meta SEO, og:, scripts |
| `auth/login.html.twig` | Page login |
| `auth/register.html.twig` | Page inscription |
| `auth/account.html.twig` | Compte utilisateur |
| `editorial/home.html.twig` | Accueil éditorial |
| `editorial/post.html.twig` | Article/page |
| `editorial/archive.html.twig` | Archive (liste par type) |
| `editorial/term.html.twig` | Terme de taxonomie |
| `editorial/_post_card.html.twig` | Carte article (partial) |
| `editorial/form.html.twig` | Formulaire public |
| `ecommerce/shop_index.html.twig` | Boutique (liste) |
| `ecommerce/shop_product.html.twig` | Fiche produit |
| `ecommerce/cart.html.twig` | Panier |
| `ecommerce/checkout.html.twig` | Commande |
| `photo/gallery/layout.html.twig` | Layout galerie photo |
| `photo/gallery/index.html.twig` | Vue galerie photo |
| `ged/documents/index.html.twig` | Bibliothèque GED |

---

## `ThemeResolver::resolveAll()`

`resolveAll()` retourne une map `nom → chemin résolu` des templates principaux.
Cette map est passée à Vue via `themeTemplates` pour que les includes inter-templates
suivent le thème actif (un `_post_card` overridé doit être utilisé même depuis
un template `home` overridé).

```twig
{# Dans un template custom qui inclut un partial #}
{{ include(themeTemplates['editorial/_post_card'], {post: post, locale: locale}) }}
```

Si le thème custom override `_post_card`, c'est sa version qui sera incluse.

> **Note** : `resolveAll()` ne couvre que les templates Editorial + layout.
> Les templates Ecommerce/Auth/Photo/GED n'en font pas encore partie.

---

## Exemple minimal : bannière custom sur la home

`templates/Frontend/themes/demo/editorial/home.html.twig` :

```twig
{% extends 'Frontend/themes/default/layout.html.twig' %}

{% block title %}{{ context.siteName }}{% endblock %}

{% block body %}
    <div class="mb-6 p-4 bg-amber-500/20 border border-amber-500/40 rounded-lg text-sm">
        Bienvenue sur le thème Demo !
    </div>

    {# Copier le reste du contenu depuis default/editorial/home.html.twig #}
{% endblock %}
```

Seul `editorial/home.html.twig` est overridé — tous les autres templates
(archive, post, shop, auth…) continuent d'utiliser `default`.
