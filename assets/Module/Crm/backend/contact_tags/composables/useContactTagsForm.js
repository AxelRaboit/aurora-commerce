import { reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { slugifyIfEmpty } from "@/shared/utils/format/slugify.js";

function buildEmptyForm(extraFields = {}) {
    const extras = {};
    for (const key of Object.keys(extraFields)) {
        extras[key] = extraFields[key].default;
    }
    return {
        label: "",
        slug: "",
        color: "#6366F1",
        ...extras,
    };
}

export function useContactTagsForm(options) {
    const {
        createPath,
        updatePath,
        reset,
        extraFields = {},
    } = options;
    const { t } = useI18n();

    const showCreate = ref(false);
    const showEdit = ref(false);
    const editingTag = ref(null);
    const editForm = reactive(buildEmptyForm(extraFields));

    function resetForm() {
        const fresh = buildEmptyForm(extraFields);
        for (const key of Object.keys(editForm)) {
            delete editForm[key];
        }
        Object.assign(editForm, fresh);
    }

    function loadFromTag(tag) {
        resetForm();
        editForm.label = tag.label ?? "";
        editForm.slug = tag.slug ?? "";
        editForm.color = tag.color ?? "#6366F1";

        for (const key of Object.keys(extraFields)) {
            const fromEntity = extraFields[key].fromEntity;
            editForm[key] = fromEntity
                ? fromEntity(tag)
                : extraFields[key].default;
        }
    }

    function buildBody() {
        return {
            ...editForm,
            label: editForm.label?.trim() ?? "",
            slug: editForm.slug?.trim() ?? "",
            color: editForm.color || "#6366F1",
        };
    }

    const {
        errors: createErrors,
        loading: createLoading,
        submit: submitCreate,
        clearErrors: clearCreateErrors,
    } = useFormAction({
        url: () => createPath,
        body: buildBody,
        onSuccess: () => {
            showCreate.value = false;
            toast.success(t("backend.crm.contact_tags.created"));
            reset();
        },
    });

    const {
        errors: editErrors,
        loading: editLoading,
        submit: submitEdit,
        clearErrors: clearEditErrors,
    } = useFormAction({
        url: () => buildPath(updatePath, { id: editingTag.value.id }),
        body: buildBody,
        onSuccess: () => {
            showEdit.value = false;
            toast.success(t("backend.crm.contact_tags.updated"));
            reset();
        },
    });

    function openCreate() {
        editingTag.value = null;
        resetForm();
        clearCreateErrors();
        showCreate.value = true;
    }

    function openEdit(tag) {
        editingTag.value = tag;
        loadFromTag(tag);
        clearEditErrors();
        showEdit.value = true;
    }

    function autoSlug() {
        editForm.slug = slugifyIfEmpty(editForm.slug, editForm.label);
    }

    return {
        showCreate,
        showEdit,
        editingTag,
        editForm,
        createErrors,
        createLoading,
        editErrors,
        editLoading,
        openCreate,
        openEdit,
        submitCreate,
        submitEdit,
        autoSlug,
    };
}
