# Namespacing des routes backend par module — décision + backlog

## Règle

**Toute** route backend est préfixée par son module : `/backend/<module>/<entité>`
+ nom de route `backend_<module>_<entité>`. Le path ET le nom suivent le
namespacing (cf. [[structure_controller]]).

**Décision user (2026-05) : on namespace TOUT**, sans exception "core/transverse
reste à plat". Donc aussi l'auth et les pages transverses :
- `/backend/users` → `/backend/platform/users`
- `/backend/settings`, `/backend/themes` → `/backend/configuration/…`
- `/backend/login`, `/backend/register`, `/backend/forgot-password` → `/backend/platform/…`
- `/backend/profile`, `/backend/search` → `/backend/general/…`

## Pourquoi

Editorial était le **premier module** du projet et avait gardé des URLs à plat
(`/backend/posts`), comme plusieurs modules historiques. La majorité des modules
récents (Crm, Ged, Ecommerce, Erp, Billing, Vault, PersonalFinance, Assistant,
Notes) namespacent déjà. Le user veut l'uniformité totale plutôt que des
exceptions au cas par cas.

## Comment l'appliquer

Méthode rodée sur Editorial (commit `17890cb2`) :
1. Sur chaque controller backend du module : changer le `#[Route]` de **classe**
   (path + `name:`). Les routes de méthode héritent du préfixe.
2. Remplacer toutes les références de nom de route (`path()`, `redirectToRoute`,
   `generateUrl`, nav `getNavSections`) — PHP + Twig.
3. URLs **hardcodées** en JS (rares) : remplacer en ancrant sur un délimiteur
   de chaîne (`'`, `"`, backtick) **avant** `/backend/` pour ne PAS casser les
   alias d'import Vite `@<module>/backend/…` (qui contiennent aussi `/backend/`).
4. Tests : passer par `urlGenerator->generate('backend_<module>_…')` plutôt que
   des URLs en dur (cf. `FormsControllerTest`), + `HttpMethodEnum`.
5. `cache:clear` + `debug:router` (0 ancien nom) + `npm run build` + `phpunit` +
   `make fix`. Commit atomique par module.

## Backlog (état)

- ✅ **Editorial** — fait (posts, post-types, forms, comments, taxonomies,
  menus, sitemap).
- ⏳ **Platform** : agencies, users, services + auth (login, register,
  forgot-password, access-request).
- ⏳ **Media** (médiathèque) : media.
- ⏳ **General** : profile, search.
- ⏳ **Outils / PasswordGenerator** : password-generator (vérifier si "Outils"
  = section nav regroupant d'autres).
- ⏳ **Photo** : galleries. **Project** : projects, plannings.
  **Configuration** : settings, themes.
- Déjà namespacés (rien à faire) : Crm, Ged, Ecommerce, Erp, Billing, Vault,
  PersonalFinance, Assistant, Notes.

## Dette connexe découverte (à traiter séparément)

`src/Module/Editorial/Menu/translations/messages.*.yaml` héberge **`backend.nav.*`**
— les libellés de **navigation globale** (sections platform/media/configuration,
items agencies/users/galleries/themes/settings…). Ce sont des trads **Core/site**,
pas éditoriales : reliquat du déménagement `Core/Menu` → `Editorial/Menu`.
À remonter vers les trads Core (ou vers chaque module propriétaire) — pas une
simple fusion dans `Editorial/translations/`.
