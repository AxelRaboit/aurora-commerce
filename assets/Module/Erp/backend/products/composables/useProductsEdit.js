import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
import { required } from "@/shared/utils/validation/validators.js";
import {
    emptyProductForm,
    buildProductPayload,
    makeImageRef,
} from "./useProductsOptions.js";

export function useProductsEdit(updatePath, reset) {
    const { t } = useI18n();

    const showEdit = ref(false);
    const editingProduct = ref(null);
    const editForm = ref(emptyProductForm());
    const editFormImage = makeImageRef(editForm);
    const { errors: editErrors, validate, clearErrors, handleErrors } = useServerErrors();
    const { loading: editLoading, request } = useRequest();

    function openEdit(product) {
        editingProduct.value = product;
        editForm.value = {
            name: product.name,
            reference: product.reference,
            description: product.description ?? "",
            price: product.price ?? "",
            currency: product.currency ?? "EUR",
            status: product.status ?? "draft",
            type: product.type ?? "physical",
            imageId: product.image?.id ?? null,
            imageUrl: product.image?.url ?? null,
            stockQuantity: product.stockQuantity ?? "",
        };
        clearErrors();
        showEdit.value = true;
    }

    async function submitEdit() {
        if (
            !validate({
                name: () =>
                    required(t("backend.erp.products.errors.name_required"))(
                        editForm.value.name,
                    ),
            })
        )
            return;
        const url = buildPath(updatePath, { id: editingProduct.value.id });
        const data = await request(url, buildProductPayload(editForm.value));
        if (!data) return;
        if (data.success) {
            showEdit.value = false;
            toast.success(t("backend.erp.products.updated"));
            reset();
        } else handleErrors(data.errors);
    }

    return {
        showEdit,
        editingProduct,
        editForm,
        editFormImage,
        editErrors,
        editLoading,
        openEdit,
        submitEdit,
    };
}
