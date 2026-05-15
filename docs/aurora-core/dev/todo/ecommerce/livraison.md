# Ecommerce — Livraison

- [ ] **ShippingMethod** — entité configurable (colissimo, express, retrait magasin…) avec règles de calcul du coût (forfait, poids, montant)
- [ ] **Shipment sur Order** — un `Order` peut générer N `Shipment` avec tracking et statut propre
- [ ] **Zones géographiques** — `Zone` (pays, régions) pour appliquer les bonnes `TaxRate` et `ShippingMethod` selon l'adresse de livraison
