import { ref } from "vue";
import { useInlineEdit } from "@/shared/composables/form/useInlineEdit.js";

export function useTiersActions(props) {
    const tiers = ref({ ...props.tiers });
    const showDeleteModal = ref(false);
    const deleting = ref(false);
    const { saveField, submit } = useInlineEdit();

    async function updateField(field, value) {
        const data = await saveField(props.updatePath, field, value);
        if (data) tiers.value = data.tiers;
    }

    async function deleteTiers() {
        if (deleting.value) return;
        deleting.value = true;
        const data = await submit(props.deletePath, null, {
            successMessage: "backend.billing.tiers.deleted",
        });
        deleting.value = false;
        showDeleteModal.value = false;
        if (data) window.location.href = props.listPath;
    }

    return { tiers, showDeleteModal, deleting, updateField, deleteTiers };
}
