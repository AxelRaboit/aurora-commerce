import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Drill-down state for the "list a budget item's transactions" modal :
 * fetches the actuals that feed a given budget item, lets the caller
 * edit (in-place via the extracted edit modal) or delete them, then
 * notifies the budget page so it refreshes its actuals / diff /
 * progress.
 *
 * @param {string} itemTransactionsPath - GET URL with `__id__` placeholder
 * @param {string} deleteTransactionPath - POST URL with `__id__` placeholder
 */
export function useBudgetItemTransactions(itemTransactionsPath, deleteTransactionPath) {
    const { t } = useI18n();
    const list = useRequest();
    const del = useRequest();

    const show = ref(false);
    const currentItem = ref(null);
    const transactions = ref([]);
    const pendingDelete = ref(null);

    async function open(item) {
        currentItem.value = item;
        transactions.value = [];
        show.value = true;
        if (!item?.id) return;
        const payload = await list.request(
            buildPath(itemTransactionsPath, { id: item.id }),
            null,
            HttpMethod.Get,
        );
        if (payload && payload.success !== false) {
            transactions.value = payload.transactions ?? [];
        }
    }

    function close() {
        show.value = false;
        currentItem.value = null;
        transactions.value = [];
        pendingDelete.value = null;
    }

    function applyUpdated(updated) {
        if (!updated) return;
        const idx = transactions.value.findIndex((t) => t.id === updated.id);
        if (idx >= 0) transactions.value[idx] = updated;
    }

    function confirmDelete(tx) {
        pendingDelete.value = tx;
    }

    async function doDelete() {
        const tx = pendingDelete.value;
        if (!tx) return false;
        const payload = await del.request(buildPath(deleteTransactionPath, { id: tx.id }));
        if (!payload || payload.success === false) return false;
        transactions.value = transactions.value.filter((row) => row.id !== tx.id);
        pendingDelete.value = null;
        toast.success(t("personal_finance.transactions.deleted"));
        return true;
    }

    return {
        show,
        currentItem,
        transactions,
        loading: list.loading,
        pendingDelete,
        deleteLoading: del.loading,
        open,
        close,
        applyUpdated,
        confirmDelete,
        doDelete,
    };
}
