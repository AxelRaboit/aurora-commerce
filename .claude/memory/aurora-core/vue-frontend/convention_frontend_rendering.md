---
name: convention_frontend_rendering
description: Règle de rendu frontend public — par défaut Twig shell + Vue comme le backend, exception pour les pages SEO-critiques en Twig SSR complet.
metadata:
  type: feedback
---

## Règle

Le frontend public suit **le même modèle que le backend admin** :

> **Par défaut : Twig shell + composant Vue monté.**
> **Exception : pages dont le contenu doit être indexé par Google → Twig SSR complet.**

### Critère de décision

| La page est… | Approche | Exemples |
|---|---|---|
| Indexée par les moteurs de recherche | **Twig SSR complet** | Post, archive, term, fiche produit, home éditoriale |
| Auth-gatée, interactive, ou non-indexée | **Twig shell + Vue** | Cart, checkout, compte, GED, galerie photo, auth |

### Pattern Twig shell

```twig
{% extends 'Frontend/themes/default/layout.html.twig' %}

{% block title %}{{ 'mon.titre'|trans }}{% endblock %}

{% block body %}
<div {{ vue_component('module/frontend/MonApp', {
    initialData: data,
    searchPath: searchPath,
}) }}></div>
{% endblock %}
```

Le layout (nav, `<head>`, meta og:) reste rendu côté serveur — les crawlers voient la structure. Le contenu interactif est géré par Vue.

### Pages actuellement en Twig SSR (SEO)

- `editorial/home`, `archive`, `post`, `term`, `form`
- `ecommerce/shop_index`, `shop_product`

### Pages actuellement en Twig shell + Vue (non-SEO)

- Toutes les pages `auth/`
- `ecommerce/cart`, `checkout`, `account_orders`, `order_show`
- `photo/gallery/index`, `photo/gallery/unlock`
- `ged/documents/index`

## Pourquoi

**Why:** Le backend admin est déjà 100% Twig shell + Vue et ça fonctionne bien. Le frontend public devrait suivre la même logique, sauf là où le SSR est requis pour le SEO. Garder du Twig pur pour des pages non-indexées est de la complexité inutile — c'est plus dur à maintenir et moins interactif.

**How to apply:** À chaque nouvelle page frontend, poser la question : *"Est-ce que Google doit indexer le contenu de cette page ?"*
- Oui → Twig SSR complet (le contenu est dans le HTML initial)
- Non → Twig shell + Vue (passer les données en props via `vue_component()`)
