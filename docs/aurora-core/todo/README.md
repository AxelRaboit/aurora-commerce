# Aurora — TODO technique

Tâches techniques identifiées mais non encore implémentées, organisées par module
puis par topic.

## Index

### Outillage / tooling

- [Commande CLI `aurora:make:entity`](make_entity_cli.md) — pendant de
  `aurora:make:module` pour scaffold une entité CRUD (5 couches Sylius) ;
  élimine ~700 lignes de boilerplate identique d'une entité à l'autre.
  Staged en 3 phases (PHP → Vue → migration auto).

### Roadmap modules

Liste des modules à venir, classés par priorité et impact.

- [Roadmap modules](module_roadmap.md)

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

### Notes — module implémenté (doc historique)

🟢 Module `src/Module/Notes/` complet — Markdown + Block tous deux en
production. Le sous-dossier [`notes/`](notes/README.md) reste comme **doc
historique d'architecture** (choix entités séparées, partage
encryption/images/tree, port d'Onyx). Plus rien à implémenter ici.

### PersonalFinance (Spendly) — nouveau module

Module `src/Module/PersonalFinance/` à créer, port du projet Spendly (Laravel) — gestion
financière personnelle avec portefeuilles multi-modes, budget mensuel, virements,
objectifs, transactions récurrentes, auto-catégorisation, statistiques, import
Excel. Décisions transverses et architecture cible dans
[`spendly/README.md`](spendly/README.md). 10 sous-modules un par entrée sidebar.

- [Tableau de bord](spendly/tableau_de_bord.md) — agrégation KPI page d'accueil
- [Vue globale](spendly/vue_globale.md) — synthèse multi-wallets avec navigation mensuelle
- [Portefeuilles](spendly/portefeuilles.md) — wallets (Budget/Simple) + members + invitations + balance adjustment
- [Transactions](spendly/transactions.md) — Income/Expense + virements (2 tx liées) + splits + attachments
- [Objectifs](spendly/objectifs.md) — savings goals auto-trackés via EventSubscriber
- [Automatiques](spendly/automatiques.md) — récurrentes mensuelles + planifiées one-off + commande cron
- [Catégories](spendly/categories.md) — taxonomie scope-wallet + system categories
- [Auto-catégorisation](spendly/auto_categorisation.md) — patterns appris description → catégorie
- [Statistiques](spendly/statistiques.md) — 6 charts analytiques sur période sélectionnable
- [Importer](spendly/importer.md) — import Excel 2-steps (upload → preview → process)

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
