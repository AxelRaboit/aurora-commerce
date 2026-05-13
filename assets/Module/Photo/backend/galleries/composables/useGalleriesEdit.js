import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
import { required } from "@/shared/utils/validation/validators.js";
import { emptyGalleryForm } from "./useGalleryForm.js";

export function useGalleriesEdit(props, reload) {
    const { t } = useI18n();

    const showEdit = ref(false);
    const editForm = ref(emptyGalleryForm());
    const editingId = ref(null);
    const editingHasPassword = ref(false);
    const { errors: editErrors, clearErrors, handleErrors } = useServerErrors();
    const { loading: editLoading, request: editRequest } = useRequest();

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
        clearErrors();
        showEdit.value = true;
    }

    async function submitEdit() {
        const errs = {};
        if (required("required")(editForm.value.title))
            errs.title = t("photo.galleries.errors.title_required");
        if (required("required")(editForm.value.slug))
            errs.slug = t("photo.galleries.errors.slug_required");
        if (Object.keys(errs).length) {
            setErrors(errs);
            return;
        }
        const data = await editRequest(
            buildPath(props.updatePath, { id: editingId.value }),
            editForm.value,
        );
        if (!data?.success) {
            handleErrors(data?.errors);
            return;
        }
        toast.success(t("photo.galleries.updated"));
        showEdit.value = false;
        reload();
    }

    return {
        showEdit,
        editForm,
        editingHasPassword,
        editErrors,
        editLoading,
        openGallery,
        openEdit,
        submitEdit,
    };
}
