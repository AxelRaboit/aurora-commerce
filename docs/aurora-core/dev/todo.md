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

### Sidebar — alias visuel des sections nav

- [ ] **Permettre de renommer visuellement les sections de la sidemenu** (général, plateforme, outils, editorial…) sans toucher au backend.
  - **Contexte** : aujourd'hui les labels viennent de `backend.nav.sections.<sectionId>` (i18n YAML par module). L'utilisateur veut pouvoir override le label affiché — par exemple renommer "Outils" en "Abc" — au niveau **présentation uniquement**. L'ID backend (`sectionId`) reste inchangé pour ne casser ni les permissions, ni `convention_navpermission_group.md`, ni les références code.
  - **Direction d'implémentation** :
    - Scope à décider : **par-user** (préférence personnelle, stockée dans `UserPreferences` ou JSON sur User) vs **global app** (config admin, ex: `ApplicationParameterEnum::NAV_SECTION_ALIASES`).
    - Stockage : map `{ sectionId: aliasLabel }` (alias vide = fallback sur le label i18n par défaut).
    - UI : panneau de config dans `/backend/profile` (si per-user) ou `/backend/dev/parameters` (si global), avec liste des sections existantes + champ texte par section.
    - Côté front : dans `useSidebarNav.js` ligne ~111, remplacer `t(\`backend.nav.sections.\${section.id}\`)` par un helper qui check d'abord l'alias puis fallback sur la traduction.
  - **Points d'attention** :
    - La recherche backend (`backend.search.sections.*` dans `AppSidebar.vue`) utilise des clés différentes — vérifier le scope (probablement à exclure de l'alias).
    - Les breadcrumbs utilisent `backend.nav.sections.<moduleId>` (cf. `convention_breadcrumb_section.md`) — décider si l'alias s'applique aussi ou non.

### CRM — Différenciation des contacts (`source` + `tags`)

- [ ] **Ajouter `source` et `tags` sur l'entité Contact** — tracer l'origine et permettre la segmentation métier.
  - **Contexte** : les contacts arrivent de plusieurs modules (création manuelle, soumission de formulaire, commande Ecommerce à venir). Sans distinction, le CRM est une liste plate impossible à segmenter.
  - **`source` (enum, automatique)** : `manual` | `form` | `order` — posé par le système au moment de la création, jamais modifiable par l'utilisateur. Permet de filtrer "contacts issus des formulaires" etc.
  - **`tags` (array JSON, manuel)** : étiquettes libres (`prospect`, `client`, `partenaire`, `newsletter`…) — posé par l'utilisateur dans le backend, ou pré-rempli par les listeners lors de la création automatique.
  - **Direction d'implémentation** :
    - Créer `src/Module/Crm/Contact/Enum/ContactSourceEnum.php` (`Manual`, `Form`, `Order`)
    - Ajouter `$source` (nullable, `ContactSourceEnum`) et `$tags` (`array`, default `[]`) dans `AbstractContact` + `ContactInterface`
    - Mettre à jour `ContactInput` / `ContactInputFactory` / `ContactManager` / `ContactSerializer`
    - `FormSubmissionCrmSyncListener` : setter `source: ContactSourceEnum::Form` + tag `newsletter` (ou le slug du form ?)
    - `OrderCrmSyncListener` (futur) : setter `source: ContactSourceEnum::Order` + tag `client`
    - UI backend : afficher `source` en badge read-only, `tags` en champ éditable (chip input)
    - Migration Doctrine
  - **Pointeur code** : `AbstractContact`, `FormSubmissionCrmSyncListener`

### CRM — Sync commandes Ecommerce → Contact

- [ ] **Créer `OrderCrmSyncListener`** — synchroniser l'acheteur d'une commande vers un Contact CRM, en opt-in.
  - **Contexte** : `FormSubmissionCrmSyncListener` crée déjà des contacts depuis les soumissions de formulaires. Les commandes Ecommerce ne font pas de même, alors que l'acheteur est un prospect/client réel (pertinent en B2B). En B2C pur, ce sync peut polluer le CRM → le rendre désactivable.
  - **Direction d'implémentation** :
    - Vérifier que `OrderCreatedEvent` existe ; sinon le créer et le dispatcher dans `OrderManager::create()`
    - Créer `src/Module/Crm/Listener/OrderCrmSyncListener.php` — même structure que `FormSubmissionCrmSyncListener` : trouver ou créer le contact par email, enrichir prénom / nom / téléphone si manquants
    - Ajouter un paramètre `crm.sync_orders` dans `ModuleParameterEnum` (opt-in, désactivable pour les usages B2C)
    - Conditionner le listener sur ce paramètre via lecture du setting au début du handler
  - **Pointeur code** : `src/Module/Crm/Listener/FormSubmissionCrmSyncListener.php` (modèle à suivre)

---

## Convention

- Chaque TODO doit avoir un **contexte** (pourquoi c'est important), une **direction d'implémentation** (comment s'y prendre), et si possible un pointeur vers le code existant lié.
- Une fois implémenté, déplacer dans le CHANGELOG ou supprimer l'entrée.
