# Ecommerce — Catalogue produit

- [ ] **Variantes produit** (`ProductVariant`) — un produit peut avoir N variantes (ex: taille, couleur) avec prix et stock propres. Actuellement `Product` est monolithique.
  - Nécessite : `ProductOption`, `ProductOptionValue`, `ProductVariant` (entity + manager + sérializer)
  - Impact : `CartItem` et `OrderLine` devront référencer `ProductVariant` plutôt que `Product`
- [ ] **Attributs produit** (`ProductAttribute`, `ProductAttributeValue`) — métadonnées libres par produit (poids, matière, dimensions…), distinct des options de variante
- [ ] **Images multiples** — `Product::$image` est unique ; passer à `ProductImage[]` avec position et alt
- [ ] **Taxons / Catégories** — arbre de catégories `Taxon` (hiérarchie parent/enfant) avec association `Product → Taxon[]`
