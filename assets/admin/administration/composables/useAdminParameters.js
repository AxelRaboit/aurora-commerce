import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { parseJson } from "@/utils/parseJson.js";

export function useAdminParameters(parameterUpdatePath, initialParameters) {
    const { t } = useI18n();

    const parsedParameters = computed(() =>
        parseJson(initialParameters, { items: [] }),
    );

    const editingKey = ref(null);
    const editingValue = ref("");
    const editSaving = ref(false);

    function startEdit(param) {
        editingKey.value = param.key;
        editingValue.value = param.value ?? "";
    }

    function cancelEdit() {
        editingKey.value = null;
        editingValue.value = "";
    }

    async function saveEdit(param) {
        if (editSaving.value) return;
        editSaving.value = true;
        try {
            const url = parameterUpdatePath.replace(
                "__key__",
                encodeURIComponent(param.key),
            );
            const response = await fetch(url, {
                method: "PATCH",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ value: editingValue.value }),
            });
            if (response.ok) {
                param.value = editingValue.value || null;
                editingKey.value = null;
                toast.success(t("admin.parameters.saved"));
            } else {
                toast.error(t("common.error"));
            }
        } finally {
            editSaving.value = false;
        }
    }

    return {
        parsedParameters,
        editingKey,
        editingValue,
        editSaving,
        startEdit,
        cancelEdit,
        saveEdit,
    };
}
