# Rollout de la convention d'extensibilité — État d'avancement

> **Document temporaire** — à supprimer une fois les 24 entités instrumentées.
>
> Référence : [`entity_extensibility_convention.md`](./entity_extensibility_convention.md)

## Vue d'ensemble

24 entités à instrumenter au pattern complet (5 couches) — voir section 2.1
de la convention. Stratégie : module par module, commits atomiques.

| État | Entités |
|---|---|
| ✅ Fait | 21 / 24 |
| 🔜 Reste | 3 / 24 |

Tests : tout doit rester vert (`php bin/phpunit`) après chaque commit.

---

## ✅ Fait — Core (5/5 instrumentables) + 4 pilotes pré-convention

Module Core terminé. Les 3 entités exclues du Core (`Locale`, `Notification`,
`Setting`) n'ont pas de CRUD admin réel — Couche 1 seule, déjà en place.

| Entité | Commit | Notes |
|---|---|---|
| Agency | `72e4989` | Référence canonique |
| Service | `d7f28d1` | Standard simple |
| Theme | `56ddf30` | Variante composables séparés (create simple / edit CSS-config) |
| Menu | `9519f10` | Cascade MenuItem + MenuItemTranslation, API standardisée DTO |
| Media + MediaFolder | `e2ac5b2` | Cascade, `upload()` reste hors hooks `auditCreated` standard |

Pilotes (instrumentés avant la convention finale, commits antérieurs) :

| Entité | Notes |
|---|---|
| User | Variante "Manager à hooks multiples" — pas d'`applyInput()` |
| Deal | Standard, hooks audit retrofitted |
| Post | Variante "editor full-page" |
| Order | Cascade OrderLine, `markPaid/markShipped/cancel` events domaine |

---

## 🔜 Reste — par module

### Editorial (4)

- [x] `Comment` — `f01f173`
- [x] `Form` (cascade : `FormField`) — `21afe8c`
- [x] `PostType` (cascade : `PostTypeField`) — `6a55216`
- [x] `Taxonomy` (cascade : `TaxonomyTerm` + `TaxonomyTermTranslation`) — `ef10e8c`

### Crm (2)

- [x] `Company` — `7d3a1b7`
- [x] `Contact` — `7d3a1b7`

### Erp (1)

- [x] `Product` — `baffbfb`

### Ecommerce (1)

- [x] `Listing` — `baffbfb`

### Photo (1)

- [x] `Gallery` (cascade : `GalleryItem`, `GalleryInvite`, `GalleryFinalization`…) — `c90e10c`

### Billing (3)

- [x] `Invoice` (cascade : `InvoiceLine`) — `3ff4ded`
- [x] `Tiers` — `3ff4ded`
- [x] `OcrJob` — `3ff4ded`

### Ged (2)

- [x] `Document` — `10a7d0c`
- [x] `DocumentCategory` — `10a7d0c`

### Project (3)

- [ ] `Project` (cascade : `ProjectColumn`, `ProjectLabel`, `ProjectSprint`, `ProjectSavedView`)
- [ ] `ProjectTask` (cascade : `ProjectTaskItem`, `ProjectTaskComment`, `ProjectTaskTimeEntry`)

---

## Checklist par entité (mémoire opérationnelle)

Pour chaque entité :

- [ ] **Couche 2 — DTO**
  - [ ] `<X>InputInterface`
  - [ ] `<X>InputFactoryInterface`
  - [ ] `<X>InputFactory` avec `#[AsAlias]`
  - [ ] `<X>Input` non-`final`, implements interface, getters
- [ ] **Couche 3 — Manager**
  - [ ] `<X>ManagerInterface` (déplacer de `Contract/` si présent)
  - [ ] `<X>Manager` non-`final`, props `protected readonly`, `#[AsAlias]`
  - [ ] Hook `create<X>()` pour chaque classe instanciée (cascade incluse)
  - [ ] Hook `applyInput()` (sauf variante User — 3 critères)
  - [ ] Hooks `auditCreated/Updated/Deleted` + `auditPayload`
  - [ ] Events domaine utilisent `[...$this->auditPayload(), 'extra' => …]`
- [ ] **Couche 4 — Serializer**
  - [ ] `<X>SerializerInterface`
  - [ ] `<X>Serializer` non-`final`, `#[AsAlias]`
- [ ] **Adaptations**
  - [ ] Controller : type-hint les interfaces
  - [ ] ViewBuilder : type-hint les interfaces
  - [ ] Tests : helpers DTO si l'API a changé
- [ ] **Couche 5 — Vue**
  - [ ] `<X>App.vue` : prop `extraFields` + slots `extra-headers`/`extra-cells`/`extra-form-fields`
  - [ ] Composable `useXxxForm.js` (renommer si `useXxxEdit.js`) avec option `extraFields`
- [ ] **Validation**
  - [ ] `npm run build` ✅
  - [ ] `php bin/phpunit` ✅
  - [ ] Commit atomique avec message descriptif

---

## Convention rappel express

3 variantes structurelles documentées :
1. **Manager à hooks multiples** (User) — 3 critères : ≥6 méthodes, pas de
   create+update simple, validation/sécurité distincte par opération
2. **Composables séparés** (User invite/edit, Theme create/edit) — quand
   forms n'ont rien en commun au-delà de `name`/`description`
3. **Editor full-page** (Post) — placement sémantique du slot, hydratation
   `onMounted`

4 règles dures :
- `createX()` pour **chaque** classe instanciée, pas d'exception
- `applyInput()` requis sauf variante 1
- 4 hooks audit (`auditCreated/Updated/Deleted` + `auditPayload`)
- Repository : limite assumée, pas d'interface aurora-core
