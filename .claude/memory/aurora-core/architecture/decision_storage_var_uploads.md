---
name: decision-storage-var-uploads
description: All Aurora-stored files live under var/uploads/ (not public/uploads/). Apache + mod_xsendfile offload in prod. URL building moved out of entities into serializers.
metadata:
  type: project
---

Aurora stocke **tous** les fichiers uploadés / générés sous
`var/uploads/<categorie>/`, hors document root. **Aucun fichier n'est
servable directement par Apache** — chaque catégorie a son controller
de serve qui vérifie l'auth et délègue à
`Aurora\Core\Storage\BinaryFileServer`.

**Why:** Avant 2026-05-16, le projet utilisait `public/uploads/` avec
"security through obscurity" via UUID (Media, Photo, PDF, OCR). Les
notes markdown ont introduit `var/uploads/notes-markdown/` avec serve
controller strict — créant une inconsistance. Décision : tout aligner
sur le modèle strict, plus de fichiers Apache-servables.

**How to apply:**
- Nouveau stockage → `var/uploads/<categorie>/` (jamais `public/`)
- Service injecte `#[Autowire('%app.upload_dir%/<categorie>')]`
  (`app.upload_dir` pointe désormais vers `var/uploads`)
- Controller serve avec route nommée `<module>_serve` (et préfixe URL
  sémantique, jamais `/uploads/...`)
- Serializer construit l'URL via `UrlGeneratorInterface::generate`,
  jamais via concaténation dans l'entité
- Pas de `Media::getPublicUrl()` ou similaire sur les entités —
  l'URL est presentation, pas domaine. Voir [[decision_4_hard_rules]]
  pour les autres séparations domaine/présentation.

**Performance prod :** `BinaryFileResponse::trustXSendfileTypeHeader(true)`
+ Apache `mod_xsendfile` + `XSendFilePath /var/www/aurora/var/uploads`.
PHP fait l'auth check, Apache sert les bytes (full speed). Dev local
sans le module → fallback PHP `readfile`, transparent.

**Doc canonique** : `docs/aurora-core/dev/storage_policy.md`.
