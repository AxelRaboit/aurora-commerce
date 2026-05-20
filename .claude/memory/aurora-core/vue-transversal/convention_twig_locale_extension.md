---
name: convention_twig_locale_extension
description: locale_flag() et locale_name() Twig pour les switchers de locale — éditer LocaleExtension pour ajouter un locale.
metadata:
  type: reference
---

## Règle

Pour afficher le drapeau ou le nom d'un locale dans Twig, utiliser les
fonctions exposées par `src/Core/Twig/LocaleExtension.php` (auto-wired via
`#[AsTwigFunction]`) :

```twig
{{ locale_flag(loc.code) ?? loc.code|upper }}
{{ locale_name(loc.code) ?? loc.code|upper }}
```

Les deux fonctions retournent `?string` — toujours prévoir un fallback avec
`?? loc.code|upper` pour les locales non répertoriés.

## Pourquoi

- Avant : `src/Core/templates/Frontend/themes/default/layout.html.twig` contenait deux
  maps hardcodées (codes → emoji flag + codes → label). Toute évolution
  exigeait d'éditer plusieurs templates et risquait le drift.
- L'extension centralise les deux maps (`FLAG_CODES`, `NAMES`) dans une seule
  classe PHP, testable et override-able côté client.

## Comment l'appliquer

1. Ajouter un locale → éditer **les deux** constantes `FLAG_CODES` et `NAMES`
   dans `LocaleExtension.php`. Pas de map dans les templates.
2. Utiliser uniquement `locale_flag()` / `locale_name()` dans les templates ;
   ne pas re-créer un dict Twig local.
3. Si une vue Vue (admin) a besoin du même mapping, exposer les données via
   le ViewBuilder — ne pas hardcoder côté JS non plus.
