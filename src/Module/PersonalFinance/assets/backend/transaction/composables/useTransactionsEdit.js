import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { evaluateAmount } from "@/shared/utils/form/amount/evaluateAmount.js";

export function useTransactionsEdit(
    updatePath,
    onUpdated,
    { extraFields = {} } = {},
) {
    const { t } = useI18n();
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
        ...pickExtras({}),
    });
    const editErrors = ref({});
    const editLoading = ref(false);

    function openEdit(transaction) {
        editingTransaction.value = transaction;
        editForm.value = {
            type: transaction.type,
            amount: transaction.amount,
            date: transaction.date,
            description: transaction.description ?? "",
            categoryId: transaction.categoryId ?? null,
            ...pickExtras(transaction),
        };
        editErrors.value = {};
        showEdit.value = true;
    }

    async function submitEdit() {
        if (!editingTransaction.value || editLoading.value) return;
        editLoading.value = true;
        editErrors.value = {};
        editForm.value.amount = evaluateAmount(editForm.value.amount);
        try {
            const url = buildPath(updatePath, {
                id: editingTransaction.value.id,
            });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    ...editForm.value,
                    description: editForm.value.description || null,
                    categoryId: editForm.value.categoryId || null,
                }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                editErrors.value = payload?.errors ?? {};
                return;
            }
            onUpdated(payload.transaction);
            toast.success(t("personal_finance.transactions.updated"));
            showEdit.value = false;
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            editLoading.value = false;
        }
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
