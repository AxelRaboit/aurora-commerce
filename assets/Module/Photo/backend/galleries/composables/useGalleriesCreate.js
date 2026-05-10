import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { emptyGalleryForm, slugify } from "./useGalleryForm.js";

export function useGalleriesCreate(createPath, reload) {
    const { t } = useI18n();

    const showCreate = ref(false);
    const newForm = ref(emptyGalleryForm());
    const slugManuallyEdited = ref(false);
    const { errors: createErrors, clearErrors, setErrors } = useForm();
    const { loading: createLoading, request: createRequest } = useRequest();

    function openCreate() {
        newForm.value = emptyGalleryForm();
        slugManuallyEdited.value = false;
        clearErrors();
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
            setErrors(errs);
            return;
        }
        const data = await createRequest(createPath, newForm.value);
        if (!data?.success) {
            setErrors(translateServerErrors(t, data?.errors));
            return;
        }
        toast.success(t("photo.galleries.created"));
        showCreate.value = false;
        reload();
    }

    return {
        showCreate,
        newForm,
        createErrors,
        createLoading,
        openCreate,
        onCreateTitleChange,
        onCreateSlugInput,
        submitCreate,
    };
}
