---
name: pattern_shared_listing_card
description: Chaque module qui affiche sa fiche en liste ≥2 fois côté frontend factorise un composant card partagé (ex: PostCard, ShopListingCard).
metadata:
  type: feedback
---

## Règle

Quand un module rend sa "fiche en liste" depuis plusieurs apps Vue frontend
(home, archive, term, search, category, tag…), créer **un seul composant
card** partagé et l'importer partout.

| Module | Composant | Consommateurs |
|---|---|---|
| Editorial | `PostCard.vue` | `HomeApp`, `ArchiveApp`, `TermApp`, recherche post |
| Ecommerce | `ShopListingCard.vue` | `ShopIndexApp`, `ShopCategoryApp`, `ShopTagApp` |

Le composant accepte l'entité brute en prop (ou un viewmodel léger) et gère
seul : layout, image, badges, lien canonique, fallback de translation
(via [[utility_pick_translation]]).

## Pourquoi

- Avant : 3 apps rendaient quasi-le-même markup avec des variations subtiles
  (`object-cover` vs `object-contain`, badge présent ou non, lien construit
  différemment…). Toute évolution UI drift sur l'une oubliait les autres.
- Un seul composant = une seule source de vérité visuelle.
- Cohérent avec [[convention_no_bem_tailwind_first]] : si le card répète des
  utilities Tailwind ≥3 fois → composant, pas classe CSS partagée.

## Comment l'appliquer

1. Module qui s'apprête à dupliquer une card dans une 2ᵉ app → extraire dans
   `assets/Module/<M>/frontend/components/<Module>Card.vue` immédiatement.
2. Toute prop optionnelle (badge, variante "horizontal" / "vertical", taille
   image) → prop avec valeur par défaut, pas une copie du composant.
3. Lié : [[convention_frontend_rendering]] (passerelle Vue → la card vit en
   Vue, pas en partial Twig).
