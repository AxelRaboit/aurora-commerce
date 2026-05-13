import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { required } from "@/shared/utils/validation/validators.js";

function emptyForm() {
    return { name: "", description: "" };
}

export function useDocumentCategoriesForm(
    createPath,
    updatePath,
    deletePath,
    reset,
) {
    const { t } = useI18n();

    const showCreate = ref(false);
    const newCategory = ref(emptyForm());
    const {
        errors: createErrors,
        validate: validateCreate,
        clearErrors: clearCreate,
        handleErrors: handleCreateErrors,
    } = useServerErrors();
    const { loading: createLoading, request: createRequest } = useRequest();

    function openCreate() {
        newCategory.value = emptyForm();
        clearCreate();
        showCreate.value = true;
    }

    async function submitCreate() {
        if (
            !validateCreate({
                name: () =>
                    required(t("backend.ged.categories.errors.name_required"))(
                        newCategory.value.name,
                    ),
            })
        )
            return;
        const data = await createRequest(createPath, newCategory.value);
        if (!data) return;
        if (data.success) {
            showCreate.value = false;
            toast.success(t("backend.ged.categories.created"));
            reset();
        } else handleCreateErrors(data.errors);
    }

    const showEdit = ref(false);
    const editingCategory = ref(null);
    const editForm = ref(emptyForm());
    const {
        errors: editErrors,
        validate: validateEdit,
        clearErrors: clearEdit,
        handleErrors: handleEditErrors,
    } = useServerErrors();
    const { loading: editLoading, request: editRequest } = useRequest();

    function openEdit(category) {
        editingCategory.value = category;
        editForm.value = {
            name: category.name,
            description: category.description ?? "",
        };
        clearEdit();
        showEdit.value = true;
    }

    async function submitEdit() {
        if (
            !validateEdit({
                name: () =>
                    required(t("backend.ged.categories.errors.name_required"))(
                        editForm.value.name,
                    ),
            })
        )
            return;
        const url = buildPath(updatePath, { id: editingCategory.value.id });
        const data = await editRequest(url, editForm.value);
        if (!data) return;
        if (data.success) {
            showEdit.value = false;
            toast.success(t("backend.ged.categories.updated"));
            reset();
        } else handleEditErrors(data.errors);
    }

    const {
        pendingDelete,
        loading: deleteLoading,
        confirm: confirmDelete,
        submit: doDelete,
    } = useDelete(deletePath, () => reset(), "backend.ged.categories.deleted");

    return {
        showCreate,
        newCategory,
        createErrors,
        createLoading,
        openCreate,
        submitCreate,
        showEdit,
        editingCategory,
        editForm,
        editErrors,
        editLoading,
        openEdit,
        submitEdit,
        pendingDelete,
        deleteLoading,
        confirmDelete,
        doDelete,
    };
}
