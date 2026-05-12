# Aurora — TODO technique

Tâches techniques identifiées mais non encore implémentées.
Chaque entrée indique le contexte, pourquoi c'est nécessaire, et la direction d'implémentation.

---

## Module Ecommerce — gaps vs Sylius

Fonctionnalités manquantes identifiées par comparaison avec Sylius.
Priorisées : les 3 premières bloquent un usage réel, les suivantes sont importantes, les dernières sont optionnelles.

### Catalogue produit

- [ ] **Variantes produit** (`ProductVariant`) — un produit peut avoir N variantes (ex: taille, couleur) avec prix et stock propres. Actuellement `Product` est monolithique.
  - Nécessite : `ProductOption`, `ProductOptionValue`, `ProductVariant` (entity + manager + sérializer)
  - Impact : `CartItem` et `OrderLine` devront référencer `ProductVariant` plutôt que `Product`
- [ ] **Attributs produit** (`ProductAttribute`, `ProductAttributeValue`) — métadonnées libres par produit (poids, matière, dimensions…), distinct des options de variante
- [ ] **Images multiples** — `Product::$image` est unique ; passer à `ProductImage[]` avec position et alt
- [ ] **Slug / SEO** — ajouter un champ `slug` sur `Product` et `Listing` pour des URLs canoniques propres (ex: `/boutique/chaussure-running-bleue`)
- [ ] **Taxons / Catégories** — arbre de catégories `Taxon` (hiérarchie parent/enfant) avec association `Product → Taxon[]`

### Tarification & fiscalité

- [ ] **TaxCategory + TaxRate** — modéliser la TVA : chaque produit a une `TaxCategory` (ex: taux normal, réduit, exonéré), chaque `TaxRate` a un taux et une zone géographique
- [ ] **Adjustments** — ligne virtuelle sur `Order` / `OrderLine` pour représenter taxes, frais de port et remises séparément du prix unitaire ; indispensable pour des totaux fiables et des factures correctes
- [ ] **Taux de change** — `CurrencyEnum` existe mais pas de table de taux ; nécessaire si multi-devises réelles

### Livraison

- [ ] **ShippingMethod** — entité configurable (colissimo, express, retrait magasin…) avec règles de calcul du coût (forfait, poids, montant)
- [ ] **Shipment sur Order** — un `Order` peut générer N `Shipment` avec tracking et statut propre
- [ ] **Zones géographiques** — `Zone` (pays, régions) pour appliquer les bonnes `TaxRate` et `ShippingMethod` selon l'adresse de livraison

### Promotions & codes promo

- [ ] **Coupon** — au minimum : code + type de réduction (montant fixe ou %) + date de validité + nb d'usages max
- [ ] **Promotion avec règles** (optionnel, scope Sylius complet) — `PromotionRule` (panier > X€, X articles…) + `PromotionAction` (remise, livraison offerte…)

### Client & adresses

- [ ] **Carnet d'adresses** — `Address` sauvegardée par `User`, sélectionnable au checkout (facturation ≠ livraison)
- [ ] **Profil client enrichi** — groupe client, historique commandes, préférences de contact (distinct du compte auth `User`)

### Moyens de paiement

- [ ] **PaymentMethod abstrait** — Aurora est hardcodé Stripe (`StripeService`). Créer une abstraction `PaymentMethodInterface` pour permettre côté client d'ajouter virement, chèque, PayPal, etc.
- [ ] **PaymentMethod configurable en backend** — activer/désactiver, configurer les clés API par méthode

### Stock avancé (optionnel pour usage B2B simple)

- [ ] **Stock par variante** — déplacer `stockQuantity` de `Product` vers `ProductVariant`
- [ ] **Mouvements de stock** — log des entrées/sorties pour traçabilité (réservation à la commande, libération à l'annulation)
- [ ] **Multi-entrepôt** (`StockLocation`) — si besoin de gérer plusieurs dépôts

### Autres

## Autres TODOs

*(à compléter au fur et à mesure)*

---

## Convention

- Chaque TODO doit avoir un **contexte** (pourquoi c'est important), une **direction d'implémentation** (comment s'y prendre), et si possible un pointeur vers le code existant lié.
- Une fois implémenté, déplacer dans le CHANGELOG ou supprimer l'entrée.
