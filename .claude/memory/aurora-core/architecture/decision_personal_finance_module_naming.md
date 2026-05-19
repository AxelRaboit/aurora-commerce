---
name: decision-personal-finance-module-naming
description: Le port du projet Spendly vers Aurora se nomme `PersonalFinance` (pas Spendly, pas Finance) — distingue de Billing (B2B) et reste générique
metadata:
  type: project
---

# Décision : module Aurora porté depuis Spendly = `PersonalFinance`

## Décision (mai 2026)

Le port du projet [Spendly](https://github.com/AxelRaboit/spendly) vers un
module Aurora se fait sous le nom **`PersonalFinance`** :

- Folder : `src/Module/PersonalFinance/` + `src/Module/PersonalFinance/assets/`
- Namespace : `Aurora\Core\Module\PersonalFinance\`
- Entités préfixées : `PersonalFinanceWallet`, `PersonalFinanceTransaction`, `PersonalFinanceCategory`, `PersonalFinanceBudget`, `PersonalFinanceGoal`, `PersonalFinanceRecurringTransaction`, `PersonalFinanceScheduledTransaction`, `PersonalFinanceCategorizationRule`, `PersonalFinanceWalletMember`, `PersonalFinanceWalletInvitation`, `PersonalFinanceBudgetItem`, `PersonalFinanceBudgetPreset`
- DB tables : `core_personal_finance_*`
- Sequences : `seq_core_personal_finance_<entity>_id`
- Routes : `/backend/personal-finance/*`
- Twig namespace : `@PersonalFinance/`
- Translations : `translations/personal_finance.<locale>.yaml`
- Storage : `var/uploads/personal-finance/`
- Console : `personal-finance:recurring:generate`

## Why

Trois alternatives écartées :

1. **`Spendly`** — pas de nom de produit dans Aurora (convention : nom de
   domaine fonctionnel, comme `Notes`/`Editorial`/`Billing`). Onyx est
   d'ailleurs devenu `Notes`, pas l'inverse.
2. **`Finance`** — trop large : Aurora a déjà `Billing` qui couvre la
   finance B2B (factures, OCR, tiers). `Finance` créerait une confusion
   sémantique chez les clients consommateurs.
3. **`Budget`** — trop étroit : cache le mode "Simple" des wallets (qui
   tracke des transactions sans budget) et risque collision sémantique
   avec la sous-entité `PersonalFinanceBudget`.

`PersonalFinance` est explicite, distingue de `Billing`, et reste générique
(pas branded).

## How to apply

- **Si nouveau code écrit pour ce module** : utiliser `PersonalFinance` partout
  (folder, namespace, entités, routes, settings…). Pas de raccourci `Finance`
  ou `Spendly` dans le code.
- **Si on parle du port en prose** : "le port de Spendly" reste valide
  (référence à l'origine), mais le module lui-même est `PersonalFinance`.
- **TODO docs** : sous `docs/aurora-core/todo/spendly/` — le folder est nommé
  d'après l'origine pour grouper, mais le contenu utilise `PersonalFinance`.
- **Distinguer de `Billing`** : `Billing` = factures B2B (clients d'agence,
  TVA, OCR, signatures). `PersonalFinance` = budget perso (wallets, virements
  inter-comptes, objectifs d'épargne, abonnements perso). Si un cas se trouve
  à cheval (ex. un freelance qui voit ses revenus perso ET facture des
  clients), c'est deux modules complémentaires, pas un seul.

Voir TODO complet et architecture cible dans
[`docs/aurora-core/todo/spendly/README.md`](../../../../docs/aurora-core/todo/spendly/README.md).
