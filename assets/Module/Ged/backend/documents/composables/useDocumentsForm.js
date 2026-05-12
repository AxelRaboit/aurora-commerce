import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

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
    const {
        errors: createErrors,
        validate: validateCreate,
        clearErrors: clearCreate,
        setErrors: setCreateErrors,
    } = useForm();
    const { loading: createLoading, request: createRequest } = useRequest();

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
        try {
            const body = new FormData();
            body.append("image", file);
            const response = await fetch(mediaUploadPath, {
                method: HttpMethod.Post,
                body,
            });
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (data.success && data.media) {
                newDoc.value.fileId = data.media.id;
                newDoc.value.fileName = data.media.fileName;
            } else {
                toast.error(t("shared.common.error"));
            }
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            uploadingCreate.value = false;
        }
    }

    async function submitCreate() {
        if (
            !validateCreate({
                title: () =>
                    required(t("backend.ged.documents.errors.title_required"))(
                        newDoc.value.title,
                    ),
            })
        )
            return;
        const data = await createRequest(createPath, { ...newDoc.value });
        if (!data) return;
        if (data.success) {
            showCreate.value = false;
            toast.success(t("backend.ged.documents.created"));
            reset();
        } else setCreateErrors(translateServerErrors(t, data.errors));
    }

    const showEdit = ref(false);
    const editingDoc = ref(null);
    const editForm = ref(emptyForm());
    const showMediaPickerEdit = ref(false);
    const uploadingEdit = ref(false);
    const {
        errors: editErrors,
        validate: validateEdit,
        clearErrors: clearEdit,
        setErrors: setEditErrors,
    } = useForm();
    const { loading: editLoading, request: editRequest } = useRequest();

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
        try {
            const body = new FormData();
            body.append("image", file);
            const response = await fetch(mediaUploadPath, {
                method: HttpMethod.Post,
                body,
            });
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (data.success && data.media) {
                editForm.value.fileId = data.media.id;
                editForm.value.fileName = data.media.fileName;
            } else {
                toast.error(t("shared.common.error"));
            }
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            uploadingEdit.value = false;
        }
    }

    async function submitEdit() {
        if (
            !validateEdit({
                title: () =>
                    required(t("backend.ged.documents.errors.title_required"))(
                        editForm.value.title,
                    ),
            })
        )
            return;
        const url = buildPath(updatePath, { id: editingDoc.value.id });
        const data = await editRequest(url, { ...editForm.value });
        if (!data) return;
        if (data.success) {
            showEdit.value = false;
            toast.success(t("backend.ged.documents.updated"));
            reset();
        } else setEditErrors(translateServerErrors(t, data.errors));
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
