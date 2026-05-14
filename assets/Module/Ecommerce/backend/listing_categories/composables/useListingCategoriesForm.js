import { reactive, ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";

function emptyTranslation() {
    return {
        name: "",
        slug: "",
        description: "",
        seoTitle: "",
        seoDescription: "",
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
        parentId: "",
        position: 0,
        imageId: null,
        imageUrl: null,
        isVisible: true,
        translations,
        ...extras,
    };
}

export function useListingCategoriesForm(options) {
    const { createPath, updatePath, locales, reset, extraFields = {} } = options;
    const { t } = useI18n();

    const showCreate = ref(false);
    const showEdit = ref(false);
    const editingCategory = ref(null);
    const editForm = reactive(buildEmptyForm(locales, extraFields));

    const formImage = computed({
        get: () => ({ id: editForm.imageId, url: editForm.imageUrl }),
        set: (v) => {
            editForm.imageId = v.id;
            editForm.imageUrl = v.url;
        },
    });

    function resetForm() {
        const fresh = buildEmptyForm(locales, extraFields);
        for (const key of Object.keys(editForm)) {
            delete editForm[key];
        }
        Object.assign(editForm, fresh);
    }

    function loadFromCategory(category) {
        resetForm();
        editForm.parentId = category.parentId ?? "";
        editForm.position = category.position ?? 0;
        editForm.imageId = category.image?.id ?? null;
        editForm.imageUrl = category.image?.url ?? null;
        editForm.isVisible = !!category.isVisible;

        for (const locale of locales) {
            const translation = category.translations?.[locale.code];
            editForm.translations[locale.code] = translation
                ? {
                      name: translation.name ?? "",
                      slug: translation.slug ?? "",
                      description: translation.description ?? "",
                      seoTitle: translation.seoTitle ?? "",
                      seoDescription: translation.seoDescription ?? "",
                  }
                : emptyTranslation();
        }

        for (const key of Object.keys(extraFields)) {
            const fromEntity = extraFields[key].fromEntity;
            editForm[key] = fromEntity ? fromEntity(category) : extraFields[key].default;
        }
    }

    function buildBody() {
        return {
            ...editForm,
            parentId: editForm.parentId === "" ? null : Number(editForm.parentId),
            imageId: editForm.imageId,
            position: Number(editForm.position) || 0,
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
            toast.success(t("backend.ecommerce.listing_categories.created"));
            reset();
        },
    });

    const {
        errors: editErrors,
        loading: editLoading,
        submit: submitEdit,
        clearErrors: clearEditErrors,
    } = useFormAction({
        url: () => buildPath(updatePath, { id: editingCategory.value.id }),
        body: buildBody,
        onSuccess: () => {
            showEdit.value = false;
            toast.success(t("backend.ecommerce.listing_categories.updated"));
            reset();
        },
    });

    function openCreate() {
        editingCategory.value = null;
        resetForm();
        clearCreateErrors();
        showCreate.value = true;
    }

    function openEdit(category) {
        editingCategory.value = category;
        loadFromCategory(category);
        clearEditErrors();
        showEdit.value = true;
    }

    return {
        showCreate,
        showEdit,
        editingCategory,
        editForm,
        formImage,
        createErrors,
        createLoading,
        editErrors,
        editLoading,
        openCreate,
        openEdit,
        submitCreate,
        submitEdit,
    };
}
