# Roadmap — Modules à venir

Inspiré de Dolibarr, cette liste recense les modules manquants dans Aurora, classés par priorité.

## État actuel

| Module | Statut |
|---|---|
| Editorial (CMS/Blog) | ✅ Core |
| CRM (contacts, entreprises, affaires) | ✅ Core |
| ERP (produits) | ✅ Core |
| Ecommerce (catalogue, panier, commandes) | ✅ Core |
| Billing (factures, avoir, OCR, tiers) | ✅ Core |
| GED (documents) | ✅ Core |
| Photo (galeries client) | ✅ Core |
| Project (projets / tâches) | ✅ Core |
| Planning / Agenda | ✅ Core |
| HR (fiches employés) | ✅ Core (entité `Employee` avec lien `User`, CRUD complet) |
| Notes (Markdown + Block / EditorJS) | ✅ Core |
| Vault (Safe + PasswordGenerator) | ✅ Core |
| PdfForm (formulaires PDF) | ✅ Core |
| Assistant (Ollama / chat IA) | ✅ Core |

---

## 🔴 Haute priorité

### PersonalFinance (Spendly)
**Port de :** [Spendly](https://github.com/AxelRaboit/spendly) (projet Laravel maison)
**Pourquoi :** Module mature et complet à porter. Couvre la gestion financière
personnelle de bout en bout — usage déjà éprouvé, design itéré, valeur immédiate
pour tout client Aurora ayant besoin de tracking de dépenses.
**Fonctionnalités cibles :**
- Portefeuilles multiples (modes Budget vs Simple) + partage Owner/Editor/Viewer
- Transactions Income/Expense + virements (2 tx liées) + splits + attachments
- Budget mensuel avec sections, carry-over, copy-from-previous, presets
- Objectifs d'épargne (auto-trackés via category)
- Transactions récurrentes (mensuelles) + planifiées (one-off)
- Catégories scope-wallet + auto-catégorisation par patterns appris
- Statistiques multi-charts + projection année
- Import Excel 2-steps
**Cible :** module `src/Module/PersonalFinance/` — voir [TODO détaillé](spendly/README.md)
**Hors scope :** Administration, plan Free/Pro/Stripe, guide de démarrage
(décision utilisateur explicite mai 2026)

---

### Contrats / Abonnements
**Inspiré de :** Dolibarr — Module Contrats  
**Pourquoi :** Génère des factures récurrentes automatiquement. Indispensable pour les modèles SaaS, maintenance, abonnements.  
**Fonctionnalités cibles :**
- Contrats avec période, montant, renouvellement
- Génération automatique de factures récurrentes
- Alertes d'échéance
- Lien vers tiers (Billing)

---

## 🟡 Valeur selon le secteur

### Support / Tickets
**Inspiré de :** Dolibarr — Module Ticket  
**Pourquoi :** Helpdesk post-vente. Lié aux contacts CRM pour un suivi 360°.  
**Fonctionnalités cibles :**
- Tickets avec statut, priorité, catégorie
- Assignation à un membre de l'équipe
- Historique des échanges
- Lien vers contacts/commandes

---

### Ressources Humaines
**Inspiré de :** Dolibarr — Module RH  
**Pourquoi :** Gestion interne de l'équipe. Moins prioritaire pour les projets client.  
**Fonctionnalités cibles :**
- Fiches employés ✅ implémentées (entité `Employee` dans `src/Module/Hr/Employee/Entity/`, lien `User`, CRUD backend complet, synchronisation agence/service via `UserAgencyServiceUpdatingEvent`)
- Gestion des congés / absences
- Notes de frais
- Organigramme (lien avec le système Manager existant dans Users)

---

### Stock / Inventaire
**Inspiré de :** Dolibarr — Module Stock  
**Pourquoi :** L'ERP actuel gère les produits mais pas les mouvements de stock.  
**Fonctionnalités cibles :**
- Entrepôts / emplacements
- Mouvements d'entrée / sortie
- Seuil d'alerte stock bas
- Inventaire périodique
- Lien avec Ecommerce (décrémentation automatique à la commande)

---

## 🟢 Long terme

### Banque / Trésorerie
**Inspiré de :** Dolibarr — Module Banque  
**Pourquoi :** Rapprochement bancaire, suivi de la trésorerie réelle vs facturée.  
**Fonctionnalités cibles :**
- Comptes bancaires
- Import de relevés (CSV/OFX)
- Rapprochement avec les factures
- Tableau de bord trésorerie

---

### Expéditions / Livraisons
**Inspiré de :** Dolibarr — Module Expéditions  
**Pourquoi :** Complète le module Ecommerce avec la logistique.  
**Fonctionnalités cibles :**
- Bons de livraison
- Suivi de transporteur
- Lien avec les commandes Ecommerce
- Gestion des retours

---

### Emailing / Campagnes
**Inspiré de :** Dolibarr — Module Emailing  
**Pourquoi :** Exploiter la base de contacts CRM pour des campagnes ciblées.  
**Fonctionnalités cibles :**
- Listes de diffusion depuis les contacts CRM
- Éditeur d'email (blocs)
- Suivi des ouvertures / clics
- Désabonnement automatique

---

## Notes d'implémentation

- Tous les nouveaux modules doivent préfixer leurs tables en `core_`
- Les modules liés au CRM (Contrats, Tickets) doivent réutiliser les entités `CrmContact` et `CrmCompany` existantes
- Chaque module doit implémenter `ModuleInterface` et être activable/désactivable via `ApplicationParameterEnum`
- Privilégier l'intégration dans le frontend via `FrontendInterface` si le module a une partie publique
