import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { DEFAULT_CURRENCY } from "@/shared/utils/format/currencies.js";

export const STATUS_TONE = {
    draft: "amber",
    active: "emerald",
    archived: "slate",
};
export const TYPE_TONE = {
    physical: "slate",
    digital: "accent",
    service: "violet",
};

export function emptyProductForm() {
    return {
        name: "",
        reference: "",
        description: "",
        price: "",
        currency: DEFAULT_CURRENCY,
        status: "draft",
        type: "physical",
        imageId: null,
        imageUrl: null,
        stockQuantity: "",
    };
}

export function buildProductPayload(form) {
    return {
        name: form.name,
        reference: form.reference,
        description: form.description,
        price: form.price === "" ? null : form.price,
        currency: form.currency,
        status: form.status,
        type: form.type,
        imageId: form.imageId,
        stockQuantity:
            form.stockQuantity === "" ? null : Number(form.stockQuantity),
    };
}

export function makeImageRef(form) {
    return computed({
        get: () => ({ id: form.value.imageId, url: form.value.imageUrl }),
        set: (v) => {
            form.value.imageId = v.id;
            form.value.imageUrl = v.url;
        },
    });
}

export function useProductsOptions() {
    const { t } = useI18n();

    const STATUS_OPTIONS = computed(() => [
        { value: "draft", label: t("backend.erp.products.status.draft") },
        { value: "active", label: t("backend.erp.products.status.active") },
        { value: "archived", label: t("backend.erp.products.status.archived") },
    ]);

    const TYPE_OPTIONS = computed(() => [
        { value: "physical", label: t("backend.erp.products.types.physical") },
        { value: "digital", label: t("backend.erp.products.types.digital") },
        { value: "service", label: t("backend.erp.products.types.service") },
    ]);

    return { STATUS_OPTIONS, STATUS_TONE, TYPE_OPTIONS, TYPE_TONE };
}
