---
name: decision-media-ged-merge-phase-1
description: Phase 1 du plan Media→GED — parité de rendu (variants + focal + URL generator) sur Document, sans toucher au moindre consommateur Media. État au 2026-05-30.
metadata:
  type: project
---

**Décision (2026-05-25)** : supprimer le module Media à terme et faire de
`Document` (GED) **l'unique entité fichier** d'Aurora. Plan complet en 5
phases dans [`docs/aurora-core/todo/media-ged-merge.md`](../../../../docs/aurora-core/todo/media-ged-merge.md).

**Why** : Media et GED font 90 % de la même chose (upload, dossiers,
versions, crop, picker, drag&drop). La duplication coûte en maintenance
et brouille l'arbo. GED ayant déjà la version la plus riche côté
admin (statuts, OCR-ready, catégories, tags), c'est `Document` qui
absorbe les responsabilités de `Media`.

**Phase 1 — État au 2026-05-30 : ✅ terminée**.

Ce qu'on a fait (purement additif, zéro régression côté Media) :

1. **`ImageVariantGenerator` déplacé** : `src/Module/Media/Library/Service/`
   → `src/Core/Storage/Service/` (générique, plus de coupling Media).
   3 consommateurs migrés (MediaManager, RebuildMediaVariantsCommand, test).

2. **`AbstractDocument` étendu** avec :
   - `focalX` / `focalY` (float nullable) — focal point [0,1] pour
     `object-position`
   - `variants` (JSON, défaut `{}`) — map `<name>` → chemin relatif
     `var/uploads/.../variants/<size>/<basename>.webp`
   - Getters/setters + signatures dans `DocumentInterface`
   - Migration `Version20260530064822` (ajoute `focal_x`, `focal_y`,
     `variants` sur `core_ged_documents`)

3. **`DocumentInput` + factory** : `focalX` / `focalY` éditables via le
   form (le picker JS clique sur l'image). **Pas** `variants` dans le
   DTO — c'est server-owned, regénéré à chaque swap de file.

4. **`DocumentManager`** : nouveau hook protégé `regenerateVariantsIfImage()`
   appelé :
   - `create()` après `applyInput`
   - `update()` quand `filePath` a changé (deleteVariants old + generate new)
   - `cropImage()` après le crop (deleteVariants old + generate new)
   - `delete()` + `bulkDelete()` — cleanup des variants sur disque

5. **`DocumentUrlGenerator`** (nouveau service, `final readonly`) — miroir
   de `MediaUrlGenerator`. API : `publicUrl`, `publicUrlAbsolute`,
   `variantUrl`, `thumbUrl` (cascade medium → large → original),
   `focalPositionCss` (retourne `"X% Y%"`). Tous null-safe.
   Pas de `getFocalPositionCss()` sur l'entité (CLAUDE.md §3bis :
   séparation domaine/présentation).

6. **`DocumentSerializer`** expose : `focalX`, `focalY`, `focalPositionCss`,
   `variants` (map name → URL). Le payload reste rétrocompatible
   (ajout de clés, rien de retiré).

7. **`CreatesStorageUrlGenerators`** trait : ajout de
   `makeDocumentUrlGenerator()` pour les tests serializer.

8. **Tests** : 12 tests `DocumentUrlGeneratorTest` (couverture complète),
   tests existants `DocumentManagerTest` / `DocumentSerializerTest` adaptés
   au nouveau ctor (1 dep en plus chacun).

**How to apply** : pour ajouter une nouvelle responsabilité de rendu image
à Document, étendre `DocumentUrlGenerator` (ou un sous-service injecté
là), **jamais** mettre du presentation logic sur `AbstractDocument`.

**Reste à faire (Phase 2 → Phase 5)** : voir le doc media-ged-merge.md.
La sortie immédiate de la Phase 1 n'a *aucun* consommateur réel — c'est
le socle qui débloquera les migrations Phase 2 (Editorial Post,
Ecommerce Listing, Erp Product, Photo Gallery, branding, profils user).

**Lié à** : [[pattern-self-owned-storage]] (versioning + crop déjà
mutualisés côté Core), [[pattern-folder-sidebar]] (parité d'admin déjà
livrée en amont).
