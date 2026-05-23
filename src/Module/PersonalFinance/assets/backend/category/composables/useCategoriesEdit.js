import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

export function useCategoriesEdit(
    updatePath,
    onUpdated,
    { extraFields = {} } = {},
) {
    const { t } = useI18n();
    const { loading: editLoading, request } = useRequest();
    const extraKeys = Object.keys(extraFields);

    function pickExtras(source) {
        return Object.fromEntries(
            extraKeys.map((k) => [
                k,
                source?.[k] ?? extraFields[k]?.default ?? null,
            ]),
        );
    }

    const showEdit = ref(false);
    const editingCategory = ref(null);
    const editForm = ref({ name: "", ...pickExtras({}) });
    const editErrors = ref({});

    function openEdit(category) {
        editingCategory.value = category;
        editForm.value = { name: category.name, ...pickExtras(category) };
        editErrors.value = {};
        showEdit.value = true;
    }

    async function submitEdit() {
        if (!editingCategory.value) return;
        editErrors.value = {};
        const payload = await request(buildPath(updatePath, { id: editingCategory.value.id }), editForm.value);
        if (!payload) return;
        if (payload.success === false) {
            editErrors.value = payload.errors ?? {};
            return;
        }
        onUpdated(payload.category);
        toast.success(t("personal_finance.categories.updated"));
        showEdit.value = false;
    }

    return {
        showEdit,
        editingCategory,
        editForm,
        editErrors,
        editLoading,
        openEdit,
        submitEdit,
    };
}
