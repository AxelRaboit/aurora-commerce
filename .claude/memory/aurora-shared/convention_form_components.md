---
name: convention_form_components
description: Toujours utiliser les composants App* (AppButton, AppInput, AppSelect…) — jamais les éléments HTML bruts dans les vues métier
metadata:
  type: feedback
---

## Règle

**Jamais d'éléments HTML bruts** dans un fichier `.vue` métier. Toujours les composants `App*` correspondants.

### Tableau de correspondance

| Besoin | Composant |
|--------|-----------|
| Action principale / submit | `AppButton variant="primary"` |
| Action secondaire | `AppButton variant="ghost"` |
| Action destructive (final confirm) | `AppButton variant="danger"` |
| Action destructive (ouvre confirm) | `AppButton variant="danger-subtle"` |
| Icône cliquable (poubelle, crayon) | `AppIconButton color="rose|accent|default"` |
| Filtre/onglet pill (multi ou mono select) | `AppTab variant="pill"` |
| Onglet sous-section (panel switcher) | `AppTab variant="underline"` |
| Champ texte | `AppInput` |
| Texte multiligne | `AppTextarea` |
| Sélection unique | `AppSelect` |
| Sélection multiple | `AppMultiselect` |
| Booléen (toggle compact) | `AppToggle` |
| Checkbox classique | `AppCheckbox` |
| Date / datetime | `AppDatePicker` (jamais `type="date"` natif !) |
| Recherche (avec icône loupe) | `AppSearchInput` |
| Upload fichier (drag&drop) | `AppFileInput` ou `AppDropZone` |
| Upload fichier (bouton trigger) | `AppFilePickerButton` |
| Couleur (swatch nu) | `AppColorSwatch` (size="sm|md") |
| Couleur (champ form avec label/error/hex) | `AppColorField` |
| Slider / range (min-max) | `AppRange` |
| Pagination (prev/next + numéros) | `AppPagination` (émet `@change` avec le numéro de page) |
| Lien texte/nav | `AppLink` (variant `admin`, `front`, `front-accent`, `front-nav`) |

### Cas légitimes pour le HTML brut
- `<div>`, `<span>`, `<p>`, `<h1>`–`<h6>`, `<table>` (présentation pure)
- `<input type="hidden">` pour CSRF tokens
- `<label>` wrapper avec `<input type="checkbox" class="sr-only">` pour toggle pill custom
- `<a class="block">` wrappant une carte/image (lien bloc)

## Règle complémentaire — Placeholder obligatoire

Tout `AppInput`, `AppTextarea`, `AppDatePicker`, `AppSearchInput` doit recevoir un `:placeholder` traduit.

```vue
<AppInput
    v-model="form.title"
    :label="t('backend.events.fields.title')"
    :placeholder="t('backend.events.fields.titlePlaceholder')"
    :required="true"
/>
```

Convention de clé i18n : `<field>Placeholder` à côté de `<field>`.

## Règle complémentaire — Date/heure : AppDatePicker uniquement

**Jamais** `<AppInput type="date">` ou `<input type="date">` natif. Toujours `AppDatePicker`.

- Sans `enable-time` → retourne `"YYYY-MM-DD"`
- Avec `:enable-time="true"` → retourne `"YYYY-MM-DDTHH:MM"`

Compatible avec `new DateTimeImmutable($input)` côté Symfony sans transformation.

## Règle complémentaire — Listes énumérées : AppSelect avec liste curée

Pour `timezone`, `country`, `language`, `currency`, `role` — toujours un `AppSelect` peuplé via une liste curée dans un composable `use<Plural>FormOptions.js`.

## Pourquoi

Cohérence visuelle, dark mode, accessibilité (focus, aria-label), gestion uniforme des erreurs, support i18n. Un `<button>` brut casse le design system et bloque les évolutions globales.

## Comment l'appliquer

```bash
# Audit : trouver les éléments HTML bruts à remplacer
grep -rEn "<button\b|<input\b|<select\b" assets/ \
    --include="*.vue" \
  | grep -v ".test." | grep -v "node_modules"
```
