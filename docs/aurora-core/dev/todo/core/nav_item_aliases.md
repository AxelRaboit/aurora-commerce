# Backend Settings → Navigation : alias sur les items (modules)

Étendre la perso de navigation actuelle (qui couvre déjà les **sections**
du menu latéral via le setting `nav_section_aliases`) pour permettre la
même chose sur **les items** (les modules eux-mêmes contenus dans les
sections).

## Contexte

- Actuellement : `/backend/settings → Navigation` permet de renommer
  les **sections** (ex: "Vault" → "Mes outils"). Stocké dans le setting
  `nav_section_aliases` (JSON `{sectionKey: customLabel}`).
- L'archi actuelle est **symétrique** et prête à recevoir le pendant
  pour les items :
  - Enum : `Aurora\Core\Setting\Enum\ApplicationParameterEnum::NavSectionAliases`
  - Twig function : `SidemenuExtension::getNavSectionAliases()`
  - Consommateur : `useSidemenuNav(navSections, activeRoute, navSectionAliases)`
- Chaque `NavItem` a déjà un attribut `route` unique → c'est la clé
  naturelle pour l'alias par item.

## Implémentation proposée — setting jumeau

### 1. Backend

- [ ] **Enum** : ajouter `NavItemAliases = 'nav_item_aliases'` dans
      `ApplicationParameterEnum` (avec ses entrées dans `labels()`,
      `descriptions()`, `defaults()` = `'{}'`, et `group` = `'navigation'`).
- [ ] **Translations** :
  - `backend.parameters.nav_item_aliases.label`
  - `backend.parameters.nav_item_aliases.description`
  (fr + en, dans `src/Core/Setting/translations/`).
- [ ] **Twig function** : ajouter `nav_item_aliases()` dans
      `Aurora\Core\Twig\SidemenuExtension` (méthode quasi identique à
      `getNavSectionAliases`).
- [ ] **Test unitaire** sur la méthode (parsing JSON + fallback `{}`).

### 2. Layout / Sidemenu

- [ ] Twig `layout.html.twig` (ou le wrapper qui injecte les props du
      sidemenu) : passer `navItemAliases: nav_item_aliases()` à
      `AppSidemenu`.
- [ ] `AppSidemenu.vue` : déclarer la prop `navItemAliases` et la
      passer à `useSidemenuNav`.
- [ ] `useSidemenuNav.js` : accepter un 4e paramètre `navItemAliases`,
      résoudre le label de chaque item via
      `navItemAliases[item.route] ?? t(item.labelKey)`.

### 3. UI Settings → Navigation

- [ ] Composant ou section dans `SettingsApp.vue` (onglet Navigation)
      pour éditer le mapping `{route: customLabel}`.
- [ ] **UX** : ~40+ items, donc afficher **groupés sous leur section**
      avec un toggle replier/déplier par section pour limiter le scroll.
- [ ] Pour chaque item :
  - Le label par défaut affiché en placeholder/grisé
  - Un champ texte vide ou avec la valeur custom
  - Bouton "Reset" pour effacer l'alias
- [ ] Bouton global "Reset tous" (vide la map).

### 4. Tests

- [ ] Test intégration : changer un alias via l'API settings →
      sidemenu affiche le nouveau label.
- [ ] Test que l'alias d'un item **introuvable** (route obsolète) ne
      casse rien (faillback au label par défaut sur les items existants).

## Décision à acter avant de coder

- **Clé d'alias par item** : on prend `item.route` (unique) — pas le
  `labelKey` (qui peut être partagé entre features), pas l'ordre (qui
  peut bouger).
- **Cohabitation avec les privilèges** : un item masqué par
  `requiredPrivilege` reste masqué — l'alias n'a aucun impact sur la
  visibilité. À documenter dans la description du setting.
- **Cohabitation avec les "hidden items" user-level** : déjà géré dans
  `core_users.hidden_nav_items`. L'alias est admin-wide (setting global),
  le hidden est par-user. Pas de collision.

## Non-buts (v1)

- Pas de réordonnancement des items via drag-drop (le hidden_nav_items
  user-level couvre déjà la perso par user).
- Pas d'alias par-locale (le setting reste mono-valeur ; les natifs i18n
  via `labelKey` sont pour les items vanilla, les alias sont des
  overrides admin globaux).
- Pas d'alias sur les icônes (juste le texte).

## Pointeurs code

- Setting actuel section : `src/Core/Setting/Enum/ApplicationParameterEnum.php:76` (`NavSectionAliases`)
- Twig extension : `src/Core/Twig/SidemenuExtension.php`
- Composable consommateur : `assets/Core/backend/sidemenu/composables/useSidemenuNav.js`
- UI Settings : `assets/Core/backend/settings/SettingsApp.vue` (chercher
  l'onglet Navigation existant pour le pattern de form)
