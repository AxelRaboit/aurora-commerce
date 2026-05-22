import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useCategoriesEdit(
    updatePath,
    onUpdated,
    { extraFields = {} } = {},
) {
    const { t } = useI18n();
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
    const editLoading = ref(false);

    function openEdit(category) {
        editingCategory.value = category;
        editForm.value = { name: category.name, ...pickExtras(category) };
        editErrors.value = {};
        showEdit.value = true;
    }

    async function submitEdit() {
        if (!editingCategory.value || editLoading.value) return;
        editLoading.value = true;
        editErrors.value = {};
        try {
            const url = buildPath(updatePath, { id: editingCategory.value.id });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(editForm.value),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                editErrors.value = payload?.errors ?? {};
                return;
            }
            onUpdated(payload.category);
            toast.success(t("personal_finance.categories.updated"));
            showEdit.value = false;
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            editLoading.value = false;
        }
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
