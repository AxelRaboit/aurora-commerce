import { reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { slugifyIfEmpty } from "@/shared/utils/format/slugify.js";

function emptyTranslation() {
    return {
        name: "",
        slug: "",
        description: "",
    };
}

function buildEmptyForm(locales, extraFields = {}) {
    const translations = {};
    for (const locale of locales) {
        translations[locale.code] = emptyTranslation();
    }
    const extras = {};
    for (const key of Object.keys(extraFields)) {
        extras[key] = extraFields[key].default;
    }
    return {
        color: "#6366F1",
        isVisible: true,
        translations,
        ...extras,
    };
}

export function useListingTagsForm(options) {
    const {
        createPath,
        updatePath,
        locales,
        reset,
        extraFields = {},
    } = options;
    const { t } = useI18n();

    const showCreate = ref(false);
    const showEdit = ref(false);
    const editingTag = ref(null);
    const editForm = reactive(buildEmptyForm(locales, extraFields));

    function resetForm() {
        const fresh = buildEmptyForm(locales, extraFields);
        for (const key of Object.keys(editForm)) {
            delete editForm[key];
        }
        Object.assign(editForm, fresh);
    }

    function loadFromTag(tag) {
        resetForm();
        editForm.color = tag.color ?? "#6366F1";
        editForm.isVisible = !!tag.isVisible;

        for (const locale of locales) {
            const translation = tag.translations?.[locale.code];
            editForm.translations[locale.code] = translation
                ? {
                      name: translation.name ?? "",
                      slug: translation.slug ?? "",
                      description: translation.description ?? "",
                  }
                : emptyTranslation();
        }

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
            color: editForm.color || "#6366F1",
            isVisible: !!editForm.isVisible,
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
            toast.success(t("backend.ecommerce.listing_tags.created"));
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
            toast.success(t("backend.ecommerce.listing_tags.updated"));
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

    function autoSlug(locale) {
        const entry = editForm.translations?.[locale];
        if (entry) entry.slug = slugifyIfEmpty(entry.slug, entry.name);
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
