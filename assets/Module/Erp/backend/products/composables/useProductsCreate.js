import { ref } from "vue";
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

export function useProductsCreate(createPath, reset) {
    const { t } = useI18n();

    const showCreate = ref(false);
    const newProduct = ref(emptyProductForm());
    const newProductImage = makeImageRef(newProduct);
    const {
        errors: createErrors,
        validate,
        clearErrors,
        handleErrors,
    } = useServerErrors();
    const { loading: createLoading, request } = useRequest();

    function openCreate() {
        newProduct.value = emptyProductForm();
        clearErrors();
        showCreate.value = true;
    }

    async function submitCreate() {
        if (
            !validate({
                name: () =>
                    required(t("backend.erp.products.errors.name_required"))(
                        newProduct.value.name,
                    ),
            })
        )
            return;
        const data = await request(
            createPath,
            buildProductPayload(newProduct.value),
        );
        if (!data) return;
        if (data.success) {
            showCreate.value = false;
            toast.success(t("backend.erp.products.created"));
            reset();
        } else handleErrors(data.errors);
    }

    return {
        showCreate,
        newProduct,
        newProductImage,
        createErrors,
        createLoading,
        openCreate,
        submitCreate,
    };
}
