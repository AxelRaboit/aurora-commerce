---
name: convention-color-picker
description: 3 composants couleur — AppColorSwatch (swatch nu), AppColorField (form), AppColorPicker (preset grid). Choisir selon le contexte.
metadata:
  type: feedback
---

# Convention : 3 composants pour les couleurs

## Règle

Selon le contexte UI :

| Besoin | Composant |
|--------|-----------|
| Swatch isolé dans un layout custom (avec label/reset autour) | `AppColorSwatch` |
| Champ form classique (label + swatch + hex affiché + error) | `AppColorField` |
| Picker complet avec presets et input hex | `AppColorPicker` |

**❌ Jamais** `<input type="color">` brut ni `<AppInput type="color">` (hack qui
contournait l'absence de composant dédié).

## AppColorSwatch

Wrap minimal du swatch HTML natif avec styling cohérent.

```vue
<AppColorSwatch v-model="color" size="sm" />  <!-- w-8 h-8 -->
<AppColorSwatch v-model="color" />            <!-- w-10 h-10 (default) -->
```

Pour un layout très custom (ex: ThemesApp avec container `bg-surface-2` +
label en colonne + reset button), composer manuellement autour de `AppColorSwatch`.

## AppColorField

Champ form complet (équivalent `AppInput` mais pour couleur).

```vue
<AppColorField
    v-model="form.color"
    :label="t('fields.color')"
    :error="errors.color"
    :show-hex="true"     <!-- default true -->
    size="md"            <!-- sm | md -->
/>
```

## AppColorPicker

Composant existant : preset grid 4×4 + input hex éditable + bouton clear.
Utilisé pour les pickers riches (vraiment "choisir une couleur").

## Why

3 fichiers avaient `<input type="color">` raw avec styling quasi-identique
(VaultApp, PlanningsApp, ThemesApp×2). PlanningsApp utilisait même
`<AppInput type="color">` comme workaround. Aucun composant ne couvrait le
swatch compact, seul `AppColorPicker` (preset grid full) existait.

## How to apply

- Pour un form simple : `AppColorField` (suit la convention des autres champs)
- Pour un swatch dans un layout custom : `AppColorSwatch` + composer le label/reset
- Pour une vraie expérience picker avec presets : `AppColorPicker`

## Source

Créé le 2026-05-13.
