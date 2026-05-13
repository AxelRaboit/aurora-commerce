import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { required } from "@/shared/utils/validation/validators.js";

function emptyForm() {
    return { name: "", parentId: null };
}

export function useDocumentFoldersForm(
    initialFolders,
    createPath,
    updatePath,
    deletePath,
) {
    const { t } = useI18n();

    const items = ref(initialFolders ?? []);

    function applyUpdatedList(data) {
        if (Array.isArray(data?.folders)) items.value = data.folders;
    }

    const showCreate = ref(false);
    const newFolder = ref(emptyForm());
    const {
        errors: createErrors,
        validate: validateCreate,
        clearErrors: clearCreate,
        handleErrors: handleCreateErrors,
    } = useServerErrors();
    const { loading: createLoading, request: createRequest } = useRequest();

    function openCreate() {
        newFolder.value = emptyForm();
        clearCreate();
        showCreate.value = true;
    }

    async function submitCreate() {
        if (
            !validateCreate({
                name: () =>
                    required(t("backend.ged.folders.errors.name_required"))(
                        newFolder.value.name,
                    ),
            })
        )
            return;
        const data = await createRequest(createPath, newFolder.value);
        if (!data) return;
        if (data.success) {
            showCreate.value = false;
            toast.success(t("backend.ged.folders.created"));
            applyUpdatedList(data);
        } else handleCreateErrors(data.errors);
    }

    const showEdit = ref(false);
    const editingFolder = ref(null);
    const editForm = ref(emptyForm());
    const {
        errors: editErrors,
        validate: validateEdit,
        clearErrors: clearEdit,
        handleErrors: handleEditErrors,
    } = useServerErrors();
    const { loading: editLoading, request: editRequest } = useRequest();

    function openEdit(folder) {
        editingFolder.value = folder;
        editForm.value = {
            name: folder.name,
            parentId: folder.parentId ?? null,
        };
        clearEdit();
        showEdit.value = true;
    }

    async function submitEdit() {
        if (
            !validateEdit({
                name: () =>
                    required(t("backend.ged.folders.errors.name_required"))(
                        editForm.value.name,
                    ),
            })
        )
            return;
        const url = buildPath(updatePath, { id: editingFolder.value.id });
        const data = await editRequest(url, editForm.value);
        if (!data) return;
        if (data.success) {
            showEdit.value = false;
            toast.success(t("backend.ged.folders.updated"));
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
            items.value = items.value.filter((folder) => folder.id !== id);
        },
        "backend.ged.folders.deleted",
    );

    return {
        items,
        parentOptions: computed(() => [
            { value: null, label: t("backend.ged.folders.noParent") },
            ...items.value.map((folder) => ({
                value: folder.id,
                label: folder.name,
            })),
        ]),
        showCreate,
        newFolder,
        createErrors,
        createLoading,
        openCreate,
        submitCreate,
        showEdit,
        editingFolder,
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
