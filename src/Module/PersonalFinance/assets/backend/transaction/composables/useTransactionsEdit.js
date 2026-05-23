import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

export function useTransactionsEdit(
    updatePath,
    onUpdated,
    { extraFields = {} } = {},
) {
    const { t } = useI18n();
    const { loading: editLoading, request } = useRequest();
    const extraKeys = Object.keys(extraFields);

    function pickExtras(source) {
        return Object.fromEntries(
            extraKeys.map((k) => [
                k,
                source?.[k] ?? extraFields[k]?.default ?? null,
            ]),
        );
    }

    const showEdit = ref(false);
    const editingTransaction = ref(null);
    const editForm = ref({
        type: "expense",
        amount: "",
        date: "",
        description: "",
        categoryId: null,
        tags: [],
        ...pickExtras({}),
    });
    const editErrors = ref({});

    function openEdit(transaction) {
        editingTransaction.value = transaction;
        editForm.value = {
            type: transaction.type,
            amount: transaction.amount,
            date: transaction.date,
            description: transaction.description ?? "",
            categoryId: transaction.categoryId ?? null,
            tags: Array.isArray(transaction.tags) ? [...transaction.tags] : [],
            ...pickExtras(transaction),
        };
        editErrors.value = {};
        showEdit.value = true;
    }

    async function submitEdit() {
        if (!editingTransaction.value) return;
        editErrors.value = {};
        editForm.value.amount = evaluateAmount(editForm.value.amount);

        const payload = await request(buildPath(updatePath, { id: editingTransaction.value.id }), {
            ...editForm.value,
            description: editForm.value.description || null,
            categoryId: editForm.value.categoryId || null,
        });
        if (!payload) return;
        if (payload.success === false) {
            editErrors.value = payload.errors ?? {};
            return;
        }
        onUpdated(payload.transaction);
        toast.success(t("personal_finance.transactions.updated"));
        showEdit.value = false;
    }

    return {
        showEdit,
        editingTransaction,
        editForm,
        editErrors,
        editLoading,
        openEdit,
        submitEdit,
    };
}
