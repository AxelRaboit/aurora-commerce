# Aurora — Storage policy

> **Statut** : règle adoptée 2026-05-16. Applique à tout nouveau
> stockage de fichier uploadé ou généré par l'application.

## Règle

**Tous les fichiers stockés par Aurora vivent sous `var/uploads/`** —
hors document root, jamais accessibles directement par Apache. Chaque
catégorie possède :

1. **Un sous-dossier dédié** sous `var/uploads/<categorie>/`
2. **Un controller de serve** qui vérifie l'auth (si besoin) et délègue
   à `Aurora\Core\Storage\BinaryFileServer`
3. **Une route Symfony sémantique** sous le préfixe propre au module
   (jamais `/uploads/...` qui faisait fuiter le détail de stockage)

## Layout

```
var/uploads/
├── .gitignore           ← contient `*\n!.gitignore`
├── media/               ← Core/Media (médias éditoriaux, posts, attachements)
├── profile-photos/      ← Core/User/Manager/UserProfilePhotoManager
├── galleries/           ← Module/Photo/Gallery (originals + variants)
├── ocr/                 ← Module/Billing/Ocr (factures en cours)
├── pdf-documents/       ← Module/PdfForm/PdfDocument (PDF signés)
├── pdf-templates/       ← Module/PdfForm/PdfTemplate (templates source)
└── notes-markdown/      ← Module/Notes/Markdown (images inline notes)
    └── {userId}/
```

## URLs servies

Chaque module expose une route serve nommée :

| Catégorie | Route | Auth |
|---|---|---|
| Médias éditoriaux | `media_serve` → `GET /media/{path}` | public |
| Photos profil | `profile_photo_serve` → `GET /profile-photos/{userId}` | public |
| Galleries Photo | (existant — `gallery_*`) | invite/PIN |
| OCR | `backend_ocr_file_serve` → `GET /backend/ocr/files/{path}` | admin |
| PDF documents | `backend_pdf_document_serve` → `GET /backend/pdf/documents/{id}` | owner |
| PDF templates | `backend_pdf_template_serve` → `GET /backend/pdf/templates/{id}` | admin |
| Notes markdown | `backend_notes_markdown_images_serve` | per-user owner |

Les `UrlGeneratorInterface::generate('xxx_serve', …)` construisent ces
URLs depuis les serializers — **les entités ne portent plus de méthode
`getPublicUrl()`**. URL = presentation, pas domaine.

## Performance prod (X-Sendfile)

`BinaryFileResponse::trustXSendfileTypeHeader(true)` est activé au boot.
En prod, Apache + `mod_xsendfile` intercepte la réponse PHP, sert le
fichier directement depuis `var/uploads/` :

```apache
# /etc/apache2/conf-available/aurora-xsendfile.conf
<IfModule mod_xsendfile.c>
    XSendFile On
    XSendFilePath /var/www/aurora/var/uploads
</IfModule>
```

Activer : `a2enmod xsendfile` (paquet `libapache2-mod-xsendfile`).

Résultat : auth/access checks restent en PHP (microsecondes), bytes
servis par Apache (full speed). Dev/test sans le module → fallback
natif PHP via `readfile()` — transparent.

## Pourquoi tout en `var/`, jamais en `public/` ?

1. **Sécurité homogène** — un seul modèle : tout passe par PHP. Plus
   de "security through obscurity" via UUID dans `public/uploads/`.
2. **Auth granulaire possible** — chaque catégorie peut évoluer sa
   politique d'accès (per-user, admin, public, signed-URL, etc.) sans
   bouger le storage.
3. **URL stables et sémantiques** — les routes sont sous le contrôle
   de Symfony, pas dépendantes du layout fichiers.
4. **Déploiement** — `var/` est conventionnellement le dossier mutable
   d'une app Symfony, déjà géré (permissions, persistence Docker,
   backups). `public/` reste pur "assets statiques compilés".

## Conséquences pour le développeur

- **Nouveau type de fichier à stocker ?**
  1. Créer `var/uploads/<categorie>/` (à la volée par le service)
  2. Service avec `#[Autowire('%app.upload_dir%/<categorie>')]`
  3. Controller de serve (route nommée `<module>_serve`)
  4. Serializer/builder utilise `UrlGeneratorInterface::generate(…)`
- **Pas de raccourci `'/uploads/...'`** — interdit en dur dans le code.
- **Tests** : `app.upload_dir` peut pointer un tmp dir en test config.
