/**
 * Manages field value interactions for the PDF canvas overlay.
 * Handles text inputs, checkboxes and radio groups.
 */
export function usePdfFieldInteraction(fieldValues, emit) {
    function updateField(fieldName, value) {
        emit("update:fieldValues", {
            ...fieldValues.value,
            [fieldName]: value,
        });
    }

    function isChecked(fieldName) {
        const value = fieldValues.value[fieldName];
        return !!value && value !== "Off" && value !== "No" && value !== "";
    }

    return { updateField, isChecked };
}
