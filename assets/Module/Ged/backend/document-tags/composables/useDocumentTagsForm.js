import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { required } from "@/shared/utils/validation/validators.js";

function emptyForm() {
    return { name: "", color: null };
}

export function useDocumentTagsForm(
    initialTags,
    createPath,
    updatePath,
    deletePath,
) {
    const { t } = useI18n();

    const items = ref(initialTags ?? []);

    function applyUpdatedList(data) {
        if (Array.isArray(data?.tags)) items.value = data.tags;
    }

    const search = ref("");
    const filteredItems = computed(() => {
        const q = search.value.trim().toLowerCase();
        if (!q) return items.value;
        return items.value.filter((tag) => tag.name.toLowerCase().includes(q));
    });

    const showCreate = ref(false);
    const newTag = ref(emptyForm());
    const {
        errors: createErrors,
        validate: validateCreate,
        clearErrors: clearCreate,
        handleErrors: handleCreateErrors,
    } = useServerErrors();
    const { loading: createLoading, request: createRequest } = useRequest();

    function openCreate() {
        newTag.value = emptyForm();
        clearCreate();
        showCreate.value = true;
    }

    async function submitCreate() {
        if (
            !validateCreate({
                name: () =>
                    required(t("backend.ged.tags.errors.name_required"))(
                        newTag.value.name,
                    ),
            })
        )
            return;
        const data = await createRequest(createPath, newTag.value);
        if (!data) return;
        if (data.success) {
            showCreate.value = false;
            toast.success(t("backend.ged.tags.created"));
            applyUpdatedList(data);
        } else handleCreateErrors(data.errors);
    }

    const showEdit = ref(false);
    const editingTag = ref(null);
    const editForm = ref(emptyForm());
    const {
        errors: editErrors,
        validate: validateEdit,
        clearErrors: clearEdit,
        handleErrors: handleEditErrors,
    } = useServerErrors();
    const { loading: editLoading, request: editRequest } = useRequest();

    function openEdit(tag) {
        editingTag.value = tag;
        editForm.value = {
            name: tag.name,
            color: tag.color ?? null,
        };
        clearEdit();
        showEdit.value = true;
    }

    async function submitEdit() {
        if (
            !validateEdit({
                name: () =>
                    required(t("backend.ged.tags.errors.name_required"))(
                        editForm.value.name,
                    ),
            })
        )
            return;
        const url = buildPath(updatePath, { id: editingTag.value.id });
        const data = await editRequest(url, editForm.value);
        if (!data) return;
        if (data.success) {
            showEdit.value = false;
            toast.success(t("backend.ged.tags.updated"));
            applyUpdatedList(data);
        } else handleEditErrors(data.errors);
    }

    const {
        pendingDelete,
        loading: deleteLoading,
        confirm: confirmDelete,
        submit: doDelete,
    } = useDelete(
        deletePath,
        (id) => {
            items.value = items.value.filter((tag) => tag.id !== id);
        },
        "backend.ged.tags.deleted",
    );

    return {
        items,
        search,
        filteredItems,
        showCreate,
        newTag,
        createErrors,
        createLoading,
        openCreate,
        submitCreate,
        showEdit,
        editingTag,
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
