import { ref, watch } from "vue";

/**
 * Manages revealed/hidden state for sensitive fields in a vault form or view.
 * Automatically clears all revealed fields when the modal closes.
 *
 * @param {import('vue').Ref<boolean>} showRef - the modal's :show prop
 */
export function useRevealedFields(showRef) {
    const revealedFields = ref(new Set());

    function toggleReveal(field) {
        const next = new Set(revealedFields.value);
        if (next.has(field)) next.delete(field);
        else next.add(field);
        revealedFields.value = next;
    }

    watch(showRef, (visible) => {
        if (!visible) revealedFields.value = new Set();
    });

    return { revealedFields, toggleReveal };
}
