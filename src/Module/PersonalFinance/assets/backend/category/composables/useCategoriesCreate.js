import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

function emptyCategoryForm(extraFields = {}) {
    const extras = Object.fromEntries(
        Object.keys(extraFields).map((k) => [
            k,
            extraFields[k]?.default ?? null,
        ]),
    );
    return { name: "", ...extras };
}

/**
 * @param {string} createPath - URL with __walletId__ placeholder.
 * @param {object} extraFields - client-extension fields.
 */
export function useCategoriesCreate(
    createPath,
    onCreated,
    { extraFields = {} } = {},
) {
    const { t } = useI18n();
    const { loading: createLoading, request } = useRequest();

    const showCreate = ref(false);
    const createForm = ref(emptyCategoryForm(extraFields));
    const createErrors = ref({});
    const targetWalletId = ref(null);

    function openCreate(walletId) {
        targetWalletId.value = walletId;
        createForm.value = emptyCategoryForm(extraFields);
        createErrors.value = {};
        showCreate.value = true;
    }

    async function submitCreate() {
        if (!targetWalletId.value) return;
        createErrors.value = {};
        const url = createPath.replace("__walletId__", String(targetWalletId.value));
        const payload = await request(url, createForm.value);
        if (!payload) return;
        if (payload.success === false) {
            createErrors.value = payload.errors ?? {};
            return;
        }
        onCreated(payload.category, targetWalletId.value);
        toast.success(t("personal_finance.categories.created"));
        showCreate.value = false;
    }

    return {
        showCreate,
        createForm,
        createErrors,
        createLoading,
        openCreate,
        submitCreate,
        targetWalletId,
    };
}
