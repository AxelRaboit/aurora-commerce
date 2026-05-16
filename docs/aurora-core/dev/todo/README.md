# Aurora — TODO technique

Tâches techniques identifiées mais non encore implémentées, organisées par module
puis par topic.

## Index

### Ecommerce — gaps vs Sylius

Fonctionnalités manquantes identifiées par comparaison avec Sylius. Les 3 premiers
topics (catalogue, tarification, livraison) bloquent un usage réel ; les suivants
sont importants ; stock avancé est optionnel.

- [Catalogue produit](ecommerce/catalogue.md) — variantes, attributs, images multiples, taxons
- [Tarification & fiscalité](ecommerce/tarification.md) — TaxCategory/TaxRate, Adjustments, taux de change
- [Livraison](ecommerce/livraison.md) — ShippingMethod, Shipment, Zones
- [Promotions & codes promo](ecommerce/promotions.md) — Coupon, PromotionRule/Action
- [Client & adresses](ecommerce/client.md) — carnet d'adresses, profil client enrichi
- [Moyens de paiement](ecommerce/paiement.md) — abstraction PaymentMethod, config backend
- [Stock avancé](ecommerce/stock.md) — par variante, mouvements, multi-entrepôt

### Notes — nouveau module

Module `src/Module/Notes/` à créer, regroupant deux sous-modules complémentaires
de prise de notes. Vue d'ensemble et arbitrages communs dans
[`notes/README.md`](notes/README.md).

- [Sous-module Markdown](notes/markdown/entity.md) — éditeur markdown + wiki-links + graph (port d'Onyx)
- [Sous-module Block](notes/block/overview.md) — éditeur block-based EditorJS

## Convention

- Un fichier par **topic** cohérent (ex : tous les TODOs catalogue dans
  `ecommerce/catalogue.md`).
- Chaque TODO contient :
  - **Contexte** — pourquoi c'est important / quel manque ça comble
  - **Direction d'implémentation** — esquisse de la solution (entités, manager, hooks…)
  - **Pointeurs code** quand pertinent
- Une fois implémenté → supprimer l'entrée (le commit/CHANGELOG fait foi).
- Quand un nouveau module accumule des TODOs → créer `todo/<module>/<topic>.md`
  + ajouter une section dans cet index.
