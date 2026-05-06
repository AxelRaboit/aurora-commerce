import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { slugifyIfEmpty } from "@/shared/utils/format/slugify.js";

export function emptyListingForm() {
    return {
        productId: "",
        slug: "",
        marketingTitle: "",
        marketingDescription: "",
        isVisibleOnShop: true,
        seoTitle: "",
        seoDescription: "",
        featuredImageId: null,
        featuredImageUrl: null,
    };
}

export function makeListingImageRef(form) {
    return computed({
        get: () => ({
            id: form.value.featuredImageId,
            url: form.value.featuredImageUrl,
        }),
        set: (v) => {
            form.value.featuredImageId = v.id;
            form.value.featuredImageUrl = v.url;
        },
    });
}

export function useListingsCreate(createPath, reset, loadProducts) {
    const { t } = useI18n();
    const showCreate = ref(false);
    const newListing = ref(emptyListingForm());
    const newListingImage = makeListingImageRef(newListing);
    const {
        errors: createErrors,
        validate,
        clearErrors,
        setErrors,
    } = useForm();
    const { loading: createLoading, request: createRequest } = useApiRequest();

    function openCreate() {
        newListing.value = emptyListingForm();
        clearErrors();
        showCreate.value = true;
    }

    function onProductChange(product, form) {
        if (product) form.slug = slugifyIfEmpty(form.slug, product.name);
    }

    async function submitCreate() {
        if (
            !validate({
                productId: () =>
                    required(
                        t("backend.ecommerce.listings.errors.product_required"),
                    )(newListing.value.productId),
                slug: () =>
                    required(
                        t("backend.ecommerce.listings.errors.slug_required"),
                    )(newListing.value.slug),
            })
        )
            return;
        const data = await createRequest(createPath, newListing.value);
        if (!data) return;
        if (data.success) {
            showCreate.value = false;
            toast.success(t("backend.ecommerce.listings.created"));
            reset();
            loadProducts();
        } else {
            if (data.errors?._global) toast.error(data.errors._global);
            setErrors(translateServerErrors(t, data.errors));
        }
    }

    return {
        showCreate,
        newListing,
        newListingImage,
        createErrors,
        createLoading,
        openCreate,
        onProductChange,
        submitCreate,
    };
}
