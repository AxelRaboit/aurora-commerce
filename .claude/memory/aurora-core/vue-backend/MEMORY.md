# Vue Backend — Vue / JS (Interface Admin)

- [convention_mobile_card_layout.md](convention_mobile_card_layout.md) — `sm:hidden` cards + `hidden sm:block` table ; footer actions ; bouton full-width mobile
- [convention_vue_form_validation.md](convention_vue_form_validation.md) — `useForm` + `required()` + `:error` ; `useI18n()` dans le composable ; reset loading sur tous les chemins
- [convention_modal_and_confirmation.md](convention_modal_and_confirmation.md) — `AppModal` (`:show + v-on:close`) + confirmation via modale, jamais `confirm()` natif
- [convention_file_picker_button.md](convention_file_picker_button.md) — `AppFilePickerButton` encapsule `<input type="file" hidden>` + `<AppButton>` trigger
- [convention_color_picker.md](convention_color_picker.md) — 3 composants couleur : `AppColorSwatch` (nu), `AppColorField` (form), `AppColorPicker` (preset grid)
