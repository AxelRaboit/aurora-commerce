# Aurora — TODO technique

Tâches techniques identifiées mais non encore implémentées, organisées par module
puis par topic.

## Index

### Outillage / tooling

- [Commande CLI `aurora:make:entity`](make_entity_cli.md) — pendant de
  `aurora:make:module` pour scaffold une entité CRUD (5 couches Sylius) ;
  élimine ~700 lignes de boilerplate identique d'une entité à l'autre.
  Staged en 3 phases (PHP → Vue → migration auto).

### Frontend / Vue

- [Audit `translateServerErrors`](translate_server_errors_audit.md) — ~18
  fichiers Vue bindent `data.errors` à `:error` sans passer le payload
  par `translateServerErrors`, ce qui affiche une clé i18n brute sous le
  champ. Mix de vrais bugs et de cas légitimes à trier (toast-only,
  composables génériques).

### Roadmap modules

Liste des modules à venir, classés par priorité et impact.

- [Roadmap modules](module_roadmap.md)

### ~~Welding — workflows de soudure réglementée~~ — ✅ V1 livrée puis extrait en client (mai 2026)

V1 livrée dans aurora-core (sprints -1 à 5 + post-V1 sprints 6-10), puis
le module entier a été extrait vers le projet client `aurora-welding`
(spécifique soudure réglementée — nucléaire RCC-M, ASME III, ISO 15614).
La doc Welding et le backlog V2 vivent maintenant dans
`aurora-welding/docs/welding/README.md`. Procédure d'extraction
généralisée :
[`../dev/extracting_a_module.md`](../dev/extracting_a_module.md).

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

### ~~PersonalFinance (Spendly)~~ — ✅ livré

Le port complet du projet Spendly vers `src/Module/PersonalFinance/` est
terminé (V1 sealed mai 2026 + V2 complète mai 2026 incluant les sessions
Excel export/import, BudgetPreset, Reset mois, tracking modes des Goals).
Le scaffold de planning sous `spendly/` a été supprimé une fois le port
clos. État détaillé + historique des sessions dans la mémoire
`project_personal_finance_port_status.md`.

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
