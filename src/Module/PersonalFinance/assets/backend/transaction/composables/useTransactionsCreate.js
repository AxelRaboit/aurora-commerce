import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";

function emptyTransactionForm(extraFields = {}) {
    const today = new Date().toISOString().slice(0, 10);
    const extras = Object.fromEntries(
        Object.keys(extraFields).map((k) => [
            k,
            extraFields[k]?.default ?? null,
        ]),
    );
    return {
        type: "expense",
        amount: "",
        date: today,
        description: "",
        categoryId: null,
        ...extras,
    };
}

export function useTransactionsCreate(
    createPath,
    onCreated,
    { extraFields = {} } = {},
) {
    const { t } = useI18n();

    const showCreate = ref(false);
    const createForm = ref(emptyTransactionForm(extraFields));
    const createErrors = ref({});
    const createLoading = ref(false);
    const targetWalletId = ref(null);

    function openCreate(walletId) {
        targetWalletId.value = walletId;
        createForm.value = emptyTransactionForm(extraFields);
        createErrors.value = {};
        showCreate.value = true;
    }

    async function submitCreate() {
        if (createLoading.value || !targetWalletId.value) return;
        createLoading.value = true;
        createErrors.value = {};
        createForm.value.amount = evaluateAmount(createForm.value.amount);
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
                body: JSON.stringify({
                    ...createForm.value,
                    description: createForm.value.description || null,
                    categoryId: createForm.value.categoryId || null,
                }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                createErrors.value = payload?.errors ?? {};
                return;
            }
            onCreated(payload.transaction, targetWalletId.value);
            toast.success(t("personal_finance.transactions.created"));
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
