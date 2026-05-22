import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

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

    const showCreate = ref(false);
    const createForm = ref(emptyCategoryForm(extraFields));
    const createErrors = ref({});
    const createLoading = ref(false);
    const targetWalletId = ref(null);

    function openCreate(walletId) {
        targetWalletId.value = walletId;
        createForm.value = emptyCategoryForm(extraFields);
        createErrors.value = {};
        showCreate.value = true;
    }

    async function submitCreate() {
        if (createLoading.value || !targetWalletId.value) return;
        createLoading.value = true;
        createErrors.value = {};
        try {
            const url = createPath.replace(
                "__walletId__",
                String(targetWalletId.value),
            );
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(createForm.value),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                createErrors.value = payload?.errors ?? {};
                return;
            }
            onCreated(payload.category, targetWalletId.value);
            toast.success(t("personal_finance.categories.created"));
            showCreate.value = false;
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            createLoading.value = false;
        }
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
