# Ecommerce — Tarification & fiscalité

- [ ] **TaxCategory + TaxRate** — modéliser la TVA : chaque produit a une `TaxCategory` (ex: taux normal, réduit, exonéré), chaque `TaxRate` a un taux et une zone géographique
- [ ] **Adjustments** — ligne virtuelle sur `Order` / `OrderLine` pour représenter taxes, frais de port et remises séparément du prix unitaire ; indispensable pour des totaux fiables et des factures correctes
- [ ] **Taux de change** — `CurrencyEnum` existe mais pas de table de taux ; nécessaire si multi-devises réelles
