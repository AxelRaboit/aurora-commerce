import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
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
        setErrors,
    } = useForm();
    const { loading: createLoading, request } = useApiRequest();

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
        } else setErrors(translateServerErrors(t, data.errors));
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
