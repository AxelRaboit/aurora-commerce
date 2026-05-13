import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
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

    const { errors: createErrors, loading: createLoading, submit: submitCreate, clearErrors: clearCreate } = useFormAction({
        rules: () => ({
            name: () =>
                required(t("backend.ged.folders.errors.name_required"))(
                    newFolder.value.name,
                ),
        }),
        url: () => createPath,
        body: () => newFolder.value,
        onSuccess: (data) => {
            showCreate.value = false;
            toast.success(t("backend.ged.folders.created"));
            applyUpdatedList(data);
        },
    });

    function openCreate() {
        newFolder.value = emptyForm();
        clearCreate();
        showCreate.value = true;
    }

    const showEdit = ref(false);
    const editingFolder = ref(null);
    const editForm = ref(emptyForm());

    const { errors: editErrors, loading: editLoading, submit: submitEdit, clearErrors: clearEdit } = useFormAction({
        rules: () => ({
            name: () =>
                required(t("backend.ged.folders.errors.name_required"))(
                    editForm.value.name,
                ),
        }),
        url: () => buildPath(updatePath, { id: editingFolder.value.id }),
        body: () => editForm.value,
        onSuccess: (data) => {
            showEdit.value = false;
            toast.success(t("backend.ged.folders.updated"));
            applyUpdatedList(data);
        },
    });

    function openEdit(folder) {
        editingFolder.value = folder;
        editForm.value = {
            name: folder.name,
            parentId: folder.parentId ?? null,
        };
        clearEdit();
        showEdit.value = true;
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
