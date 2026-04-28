import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { usePaginatedFetch } from "@/shared/composables/api/usePaginatedFetch.js";

export function useAdminParameters(
    parametersPath,
    parameterUpdatePath,
    initialParameters,
    initialSearch,
) {
    const { t } = useI18n();

    const searchInput = ref(initialSearch ?? "");

    const { items, page, totalPages, goToPage, reset } = usePaginatedFetch(
        parametersPath,
        () => ({ search: searchInput.value || undefined }),
        null,
        initialParameters,
    );

    function performSearch() {
        reset();
    }

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
                method: HttpMethod.Patch,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ value: editingValue.value }),
            });
            if (response.ok) {
                param.value = editingValue.value || null;
                editingKey.value = null;
                toast.success(t("admin.parameters.saved"));
            } else {
                toast.error(t("shared.common.error"));
            }
        } finally {
            editSaving.value = false;
        }
    }

    return {
        items,
        page,
        totalPages,
        goToPage,
        searchInput,
        performSearch,
        editingKey,
        editingValue,
        editSaving,
        startEdit,
        cancelEdit,
        saveEdit,
    };
}
