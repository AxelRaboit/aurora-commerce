import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

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
        tags: [],
        ...extras,
    };
}

export function useTransactionsCreate(
    createPath,
    onCreated,
    { extraFields = {} } = {},
) {
    const { t } = useI18n();
    const { loading: createLoading, request } = useRequest();

    const showCreate = ref(false);
    const createForm = ref(emptyTransactionForm(extraFields));
    const createErrors = ref({});
    const targetWalletId = ref(null);

    /**
     * @param {number|string} walletId
     * @param {object} [prefill] - shallow-merged into the empty form so
     *        callers (e.g., the Budgets page) can pre-set type / categoryId
     *        / description / date / amount before showing the modal.
     */
    function openCreate(walletId, prefill = {}) {
        targetWalletId.value = walletId;
        createForm.value = { ...emptyTransactionForm(extraFields), ...prefill };
        createErrors.value = {};
        showCreate.value = true;
    }

    async function submitCreate() {
        if (!targetWalletId.value) return;
        createErrors.value = {};
        createForm.value.amount = evaluateAmount(createForm.value.amount);

        const url = createPath.replace("__walletId__", String(targetWalletId.value));
        const payload = await request(url, {
            ...createForm.value,
            description: createForm.value.description || null,
            categoryId: createForm.value.categoryId || null,
        });
        if (!payload) return;
        if (payload.success === false) {
            createErrors.value = payload.errors ?? {};
            return;
        }
        onCreated(payload.transaction, targetWalletId.value);
        toast.success(t("personal_finance.transactions.created"));
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
