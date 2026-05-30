---
name: decision-media-ged-merge-done
description: Fusion Media → GED terminée (2026-05-30). `Document` est l'unique entité-fichier d'Aurora ; le module Media a été supprimé en totalité. Pointe vers les détails par phase.
metadata:
  type: project
---

**Statut (2026-05-30) : ✅ toutes les phases du merge Media → GED sont
livrées en local sur la branche `develop` (10 commits, non poussés).**

L'écosystème Aurora n'a désormais **plus qu'une seule entité-fichier** :
`Aurora\Module\Ged\Document\Entity\Document`. Le module `Media` a été
intégralement supprimé (entités, manager, serializers, repos,
controllers, Vue, templates, i18n, tables `core_media*`).

**Pourquoi cette mémoire racine** : si on parle de "Media" ou
"MediaUrlGenerator" dans un contexte de code, c'est une **référence
historique** (dans un commentaire de migration, un docblock de service,
ou une mémoire qui n'a pas encore été nettoyée). Le service utilisable
aujourd'hui est :

- `Aurora\Module\Ged\Document\Service\DocumentUrlGenerator` — toute URL
  d'asset (public, absolute, variant, focal CSS, thumb-cascade).
- `Aurora\Core\Storage\Service\UploadPathResolver` — résoudre un chemin
  relatif `var/uploads/...` en chemin absolu (renommé depuis
  `MediaPathResolver`).
- `Aurora\Core\Storage\Service\ImageVariantGenerator` — générer les
  thumbnails responsive WebP (déplacé en Phase 1).

**Repères chronologiques** :
- Phase 1 (2026-05-30) : parité de rendu — `DocumentUrlGenerator`,
  focal point, variants JSON sur `AbstractDocument`. Voir
  [[decision-media-ged-merge-phase-1]].
- Phases 2.1 → 2.6 : migration FK par module (Erp Product, Ecommerce
  Listing/ListingCategory, Photo Gallery + Item, Editorial Post +
  PostTranslation.og_image, branding settings + theme logo).
- Phase 3 : remapping des `mediaId` JSONB embarqués dans
  `core_post_translations.blocks` et `core_block_notes.blocks`
  (`Version20260530080934`).
- Phase 4 : picker unifié sur `DocumentPickerModal`, retrait du nav
  `/backend/media/media`, suppression de `mediaPicker.js`.
- Phase 5 : `src/Module/Media/` supprimé + tables droppées + 7 438
  lignes nettes en moins.

**Pièges rencontrés à conserver** :
- `Version20260530082245` (drop tables) avait été appliquée vide à
  cause d'une migration générée puis exécutée avant d'avoir écrit son
  contenu. Migration de rattrapage `Version20260530083658` ajoutée le
  même jour. Cf. [[pitfall-empty-migration-registered]].
- `setting_key` (et pas `key`) est le nom de colonne sur
  `core_settings`. Bien vérifier le `ORM\Column(name: ...)` avant
  d'écrire une migration SQL.
- L'opérateur PostgreSQL `?` (JSONB key-exists) entre en conflit avec
  les paramètres préparés Doctrine. Préférer `field->>'key' IS NOT
  NULL`.
- `core_themes.config` est typé `json` (pas `jsonb`) → caster
  explicitement `config::jsonb` avant d'utiliser `jsonb_set`, puis
  caster le résultat `::json`.

Plan détaillé dans
[docs/aurora-core/todo/media-ged-merge.md](../../../../docs/aurora-core/todo/media-ged-merge.md)
(le fichier est conservé pour traçabilité, marqué DONE).
