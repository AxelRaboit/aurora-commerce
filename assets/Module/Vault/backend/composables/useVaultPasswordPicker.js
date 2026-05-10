import { ref } from "vue";

export function useVaultPasswordPicker(form) {
    const show = ref(false);
    const activeField = ref(null);

    function open(field) {
        activeField.value = field;
        show.value = true;
    }

    function close() {
        show.value = false;
    }

    function apply(value) {
        if (activeField.value) {
            form.value.fields[activeField.value] = value;
        }
        close();
    }

    return { show, open, close, apply };
}
