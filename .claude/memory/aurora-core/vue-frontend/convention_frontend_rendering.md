---
name: convention_frontend_rendering
description: Tous les templates frontend sont des passerelles Vue — le head Twig porte le SEO, le body monte un composant Vue.
metadata:
  type: feedback
---

## Règle

**TOUS les templates frontend sont des passerelles Vue.** Plus de SSR Twig complet, plus de mélange. Le pattern unique :

- `<head>` rendu côté serveur via blocs Twig (`title`, `og_image`, `canonical`, `robots`, `jsonld`) → les crawlers voient les meta SEO **avant** que Vue ne mount.
- `{% block body %}` contient **uniquement** un seul `<div {{ vue_component(...) }}></div>`.

> L'ancienne règle "Twig SSR pour les pages indexées" est **dépréciée**. Le SEO passe désormais par les meta du `<head>` (title, og:image, canonical, JSON-LD) — pas par du contenu Twig dans le body.

### Pattern type

```twig
{% extends 'Frontend/themes/default/layout.html.twig' %}

{% block title %}{{ post.title }}{% endblock %}
{% block canonical %}{{ url('frontend_post_show', { slug: post.slug }) }}{% endblock %}
{% block og_image %}{{ post.coverUrl }}{% endblock %}
{% block jsonld %}{{ post.jsonLd|raw }}{% endblock %}

{% block body %}
<div {{ vue_component('Editorial/frontend/PostShowApp', {
    post: post,
    relatedPosts: relatedPosts,
}) }}></div>
{% endblock %}
```

### Pages concernées

Tout `templates/Module/Editorial/frontend/` (post, term, archive, home, form) et tout `templates/Module/Ecommerce/frontend/` (shop, category, tag, product, account, order) — `cart.html.twig` et `checkout.html.twig` étaient déjà des passerelles, ils restent inchangés.

Les partials Twig `editorial/_post_card.html.twig` et `editorial/_pagination.html.twig` ont été **supprimés** — leurs équivalents Vue (`PostCard.vue`, `AppPagination`) prennent le relais.

## Pourquoi

**Twig shell partout** est cohérent avec le backend admin (déjà 100% Twig shell + Vue). Le head meta côté serveur sert le SEO (crawlers, Open Graph, schema.org), le body interactif vit en Vue — pas de duplication SSR/CSR, pas de drift entre deux rendus, et la stack JS profite uniformément des composants partagés (`PostCard`, `ShopListingCard`, `AppPagination`, etc.).

Voir aussi : [[convention_no_bem_tailwind_first]], [[structure_template_folders]].

## Comment l'appliquer

1. Nouvelle page frontend → créer `<Module>/frontend/<feature>/index.html.twig` (cf [[structure_template_folders]]), extends le layout du thème, override les blocs `<head>` pertinents.
2. `{% block body %}` = un seul `vue_component(...)`. Aucune logique métier, aucun markup HTML.
3. Le composant Vue reçoit toutes les données nécessaires via les props (passées par le `*FrontendViewBuilder`).
4. SEO = blocs `<head>` — vérifier que `title`, `canonical`, et au moins `og_image` (ou `jsonld`) sont définis pour les pages indexables.
