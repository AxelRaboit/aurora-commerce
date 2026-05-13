import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { required } from "@/shared/utils/validation/validators.js";

function emptyForm() {
    return { name: "", description: "" };
}

export function useDocumentCategoriesForm(createPath, updatePath, deletePath, reset) {
    const { t } = useI18n();

    // ── Create ───────────────────────────────────────────────────────────────
    const showCreate = ref(false);
    const newCategory = ref(emptyForm());

    const { errors: createErrors, loading: createLoading, submit: submitCreate, clearErrors: clearCreate } = useFormAction({
        rules: () => ({
            name: () => required(t("backend.ged.categories.errors.name_required"))(newCategory.value.name),
        }),
        url:  () => createPath,
        body: () => newCategory.value,
        onSuccess: () => {
            showCreate.value = false;
            toast.success(t("backend.ged.categories.created"));
            reset();
        },
    });

    function openCreate() {
        newCategory.value = emptyForm();
        clearCreate();
        showCreate.value = true;
    }

    // ── Edit ─────────────────────────────────────────────────────────────────
    const showEdit = ref(false);
    const editingCategory = ref(null);
    const editForm = ref(emptyForm());

    const { errors: editErrors, loading: editLoading, submit: submitEdit, clearErrors: clearEdit } = useFormAction({
        rules: () => ({
            name: () => required(t("backend.ged.categories.errors.name_required"))(editForm.value.name),
        }),
        url:  () => buildPath(updatePath, { id: editingCategory.value.id }),
        body: () => editForm.value,
        onSuccess: () => {
            showEdit.value = false;
            toast.success(t("backend.ged.categories.updated"));
            reset();
        },
    });

    function openEdit(category) {
        editingCategory.value = category;
        editForm.value = { name: category.name, description: category.description ?? "" };
        clearEdit();
        showEdit.value = true;
    }

    // ── Delete ────────────────────────────────────────────────────────────────
    const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } =
        useDelete(deletePath, () => reset(), "backend.ged.categories.deleted");

    return {
        showCreate, newCategory, createErrors, createLoading, openCreate, submitCreate,
        showEdit, editingCategory, editForm, editErrors, editLoading, openEdit, submitEdit,
        pendingDelete, deleteLoading, confirmDelete, doDelete,
    };
}
