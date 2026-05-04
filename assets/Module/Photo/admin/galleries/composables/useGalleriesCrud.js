import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { toast } from "vue-sonner";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { emptyGalleryForm, slugify } from "./useGalleryForm.js";

export function useGalleriesCrud(props, reload) {
    const { t } = useI18n();

    // ── Create ────────────────────────────────────────────────────────────────
    const showCreate = ref(false);
    const newForm = ref(emptyGalleryForm());
    const slugManuallyEdited = ref(false);
    const {
        errors: createErrors,
        clearErrors: clearCreate,
        setErrors: setCreateErrors,
    } = useForm();
    const { loading: createLoading, request: createRequest } = useApiRequest();

    function openCreate() {
        newForm.value = emptyGalleryForm();
        slugManuallyEdited.value = false;
        clearCreate();
        showCreate.value = true;
    }

    function onCreateTitleChange(value) {
        newForm.value.title = value;
        if (!slugManuallyEdited.value) newForm.value.slug = slugify(value);
    }

    function onCreateSlugInput(value) {
        newForm.value.slug = value;
        slugManuallyEdited.value = true;
    }

    async function submitCreate() {
        const errs = {};
        if (required("required")(newForm.value.title))
            errs.title = t("photo.galleries.errors.title_required");
        if (required("required")(newForm.value.slug))
            errs.slug = t("photo.galleries.errors.slug_required");
        if (Object.keys(errs).length) {
            setCreateErrors(errs);
            return;
        }
        const data = await createRequest(props.createPath, newForm.value);
        if (!data?.success) {
            setCreateErrors(translateServerErrors(t, data?.errors));
            return;
        }
        toast.success(t("photo.galleries.created"));
        showCreate.value = false;
        reload();
    }

    // ── Edit ──────────────────────────────────────────────────────────────────
    const showEdit = ref(false);
    const editForm = ref(emptyGalleryForm());
    const editingId = ref(null);
    const editingHasPassword = ref(false);
    const {
        errors: editErrors,
        clearErrors: clearEdit,
        setErrors: setEditErrors,
    } = useForm();
    const { loading: editLoading, request: editRequest } = useApiRequest();

    function openGallery(g) {
        window.location.href = buildPath(props.editPath, { id: g.id });
    }

    function openEdit(g) {
        editingId.value = g.id;
        editingHasPassword.value = !!g.hasPassword;
        editForm.value = {
            title: g.title ?? "",
            slug: g.slug ?? "",
            description: g.description ?? "",
            password: "",
            clearPassword: false,
            expiresAt: g.expiresAt ? g.expiresAt.slice(0, 10) : "",
            allowOriginals: !!g.allowOriginals,
            allowZipDownload: !!g.allowZipDownload,
            picksRequireIdentity: !!g.picksRequireIdentity,
            maxPicks: g.maxPicks ?? "",
            allowVisitorComments: !!g.allowVisitorComments,
            watermarkEnabled: !!g.watermarkEnabled,
            watermarkText: g.watermarkText ?? "",
            clientContactId: g.client?.id ?? null,
            clientLabel: g.client
                ? `${g.client.name}${g.client.email ? " — " + g.client.email : ""}`
                : null,
            coverMediaId: g.coverMediaId ?? null,
            coverMediaUrl: g.coverMediaUrl ?? null,
        };
        clearEdit();
        showEdit.value = true;
    }

    async function submitEdit() {
        const errs = {};
        if (required("required")(editForm.value.title))
            errs.title = t("photo.galleries.errors.title_required");
        if (required("required")(editForm.value.slug))
            errs.slug = t("photo.galleries.errors.slug_required");
        if (Object.keys(errs).length) {
            setEditErrors(errs);
            return;
        }
        const data = await editRequest(
            buildPath(props.updatePath, { id: editingId.value }),
            editForm.value,
        );
        if (!data?.success) {
            setEditErrors(translateServerErrors(t, data?.errors));
            return;
        }
        toast.success(t("photo.galleries.updated"));
        showEdit.value = false;
        reload();
    }

    // ── Delete ────────────────────────────────────────────────────────────────
    const {
        pendingDelete,
        loading: deleteLoading,
        confirm: confirmDelete,
        submit: doDelete,
    } = useDelete(props.deletePath, () => reload(), "photo.galleries.deleted");

    return {
        showCreate,
        newForm,
        createErrors,
        createLoading,
        openCreate,
        onCreateTitleChange,
        onCreateSlugInput,
        submitCreate,
        showEdit,
        editForm,
        editingHasPassword,
        editErrors,
        editLoading,
        openGallery,
        openEdit,
        submitEdit,
        pendingDelete,
        deleteLoading,
        confirmDelete,
        doDelete,
    };
}
