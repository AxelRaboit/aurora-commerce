import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { usePaginatedFetch } from "@/shared/composables/http/usePaginatedFetch.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";

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
    const { loading: editSaving, request } = useRequest();

    function startEdit(param) {
        editingKey.value = param.key;
        editingValue.value = param.value ?? "";
    }

    function cancelEdit() {
        editingKey.value = null;
        editingValue.value = "";
    }

    async function saveEdit(param) {
        const url = parameterUpdatePath.replace(
            "__key__",
            encodeURIComponent(param.key),
        );
        const data = await request(
            url,
            { value: editingValue.value },
            HttpMethod.Patch,
        );
        if (data !== null) {
            param.value = editingValue.value || null;
            editingKey.value = null;
            toast.success(t("backend.parameters.saved"));
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
