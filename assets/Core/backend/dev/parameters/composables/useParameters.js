import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { usePaginatedFetch } from "@/shared/composables/http/usePaginatedFetch.js";

export function useParameters(
    parametersPath,
    parameterUpdatePath,
    initialParameters,
    initialSearch,
    initialGroup,
) {
    const { t } = useI18n();

    const searchInput = ref(initialSearch ?? "");
    const groupFilter = ref(initialGroup ?? "");

    const { items, page, totalPages, goToPage, reset, load } =
        usePaginatedFetch(
            parametersPath,
            () => ({
                search: searchInput.value || undefined,
                group: groupFilter.value || undefined,
            }),
            null,
            initialParameters,
        );

    function performSearch() {
        reset();
    }

    function performGroupFilter() {
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
                toast.success(t("backend.parameters.saved"));
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
        load,
        reset,
        searchInput,
        groupFilter,
        performSearch,
        performGroupFilter,
        editingKey,
        editingValue,
        editSaving,
        startEdit,
        cancelEdit,
        saveEdit,
    };
}
