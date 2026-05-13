import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { required } from "@/shared/utils/validation/validators.js";

export const DOCUMENT_STATUS_BADGE = {
    draft: "gray",
    published: "emerald",
    archived: "accent",
};

function emptyForm() {
    return {
        title: "",
        description: "",
        status: "draft",
        categoryId: null,
        tagIds: [],
        folderId: null,
        fileId: null,
        fileName: null,
    };
}

export function useDocumentsForm(
    createPath,
    updatePath,
    deletePath,
    reset,
    mediaUploadPath = "",
) {
    const { t } = useI18n();
    const { request } = useRequest();

    const statusOptions = [
        { value: "draft", label: t("backend.ged.documents.status_draft") },
        {
            value: "published",
            label: t("backend.ged.documents.status_published"),
        },
        {
            value: "archived",
            label: t("backend.ged.documents.status_archived"),
        },
    ];

    const showCreate = ref(false);
    const newDoc = ref(emptyForm());
    const showMediaPickerCreate = ref(false);
    const uploadingCreate = ref(false);

    const { errors: createErrors, loading: createLoading, submit: submitCreate, clearErrors: clearCreate } = useFormAction({
        rules: () => ({
            title: () =>
                required(t("backend.ged.documents.errors.title_required"))(
                    newDoc.value.title,
                ),
        }),
        url: () => createPath,
        body: () => ({ ...newDoc.value }),
        onSuccess: () => {
            showCreate.value = false;
            toast.success(t("backend.ged.documents.created"));
            reset();
        },
    });

    function openCreate() {
        newDoc.value = emptyForm();
        clearCreate();
        showCreate.value = true;
    }
    function onFilePickedCreate(media) {
        newDoc.value.fileId = media.id;
        newDoc.value.fileName = media.fileName;
        showMediaPickerCreate.value = false;
    }
    async function onLocalFileCreate(file) {
        if (!mediaUploadPath || !file) return;
        uploadingCreate.value = true;
        const rawBody = new FormData();
        rawBody.append("image", file);
        const data = await request(mediaUploadPath, null, { rawBody });
        uploadingCreate.value = false;
        if (!data) return;
        if (data.success && data.media) {
            newDoc.value.fileId = data.media.id;
            newDoc.value.fileName = data.media.fileName;
        } else {
            toast.error(t("shared.common.error"));
        }
    }

    const showEdit = ref(false);
    const editingDoc = ref(null);
    const editForm = ref(emptyForm());
    const showMediaPickerEdit = ref(false);
    const uploadingEdit = ref(false);

    const { errors: editErrors, loading: editLoading, submit: submitEdit, clearErrors: clearEdit } = useFormAction({
        rules: () => ({
            title: () =>
                required(t("backend.ged.documents.errors.title_required"))(
                    editForm.value.title,
                ),
        }),
        url: () => buildPath(updatePath, { id: editingDoc.value.id }),
        body: () => ({ ...editForm.value }),
        onSuccess: () => {
            showEdit.value = false;
            toast.success(t("backend.ged.documents.updated"));
            reset();
        },
    });

    function openEdit(doc) {
        editingDoc.value = doc;
        editForm.value = {
            title: doc.title,
            description: doc.description ?? "",
            status: doc.status,
            categoryId: doc.categoryId ?? null,
            tagIds: doc.tagIds ?? [],
            folderId: doc.folderId ?? null,
            fileId: doc.fileId,
            fileName: doc.fileName,
        };
        clearEdit();
        showEdit.value = true;
    }
    function onFilePickedEdit(media) {
        editForm.value.fileId = media.id;
        editForm.value.fileName = media.fileName;
        showMediaPickerEdit.value = false;
    }
    async function onLocalFileEdit(file) {
        if (!mediaUploadPath || !file) return;
        uploadingEdit.value = true;
        const rawBody = new FormData();
        rawBody.append("image", file);
        const data = await request(mediaUploadPath, null, { rawBody });
        uploadingEdit.value = false;
        if (!data) return;
        if (data.success && data.media) {
            editForm.value.fileId = data.media.id;
            editForm.value.fileName = data.media.fileName;
        } else {
            toast.error(t("shared.common.error"));
        }
    }

    const {
        pendingDelete,
        loading: deleteLoading,
        confirm: confirmDelete,
        submit: doDelete,
    } = useDelete(deletePath, () => reset(), "backend.ged.documents.deleted");

    return {
        statusOptions,
        showCreate,
        newDoc,
        showMediaPickerCreate,
        uploadingCreate,
        createErrors,
        createLoading,
        openCreate,
        onFilePickedCreate,
        onLocalFileCreate,
        submitCreate,
        showEdit,
        editingDoc,
        editForm,
        showMediaPickerEdit,
        uploadingEdit,
        editErrors,
        editLoading,
        openEdit,
        onFilePickedEdit,
        onLocalFileEdit,
        submitEdit,
        pendingDelete,
        deleteLoading,
        confirmDelete,
        doDelete,
    };
}
