import { computed, ref } from "vue";
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
 * The list is paginated server-side and accumulated locally — pair
 * with `useInfiniteScroll` to drive `loadMore` from a sentinel
 * scrolling into view.
 *
 * @param {string} itemTransactionsPath - GET URL with `__id__` placeholder. Endpoint returns `{ items, page, totalPages, total }`.
 * @param {string} deleteTransactionPath - POST URL with `__id__` placeholder.
 */
export function useBudgetItemTransactions(
    itemTransactionsPath,
    deleteTransactionPath,
) {
    const { t } = useI18n();
    const list = useRequest();
    const del = useRequest();

    const show = ref(false);
    const currentItem = ref(null);
    const transactions = ref([]);
    const page = ref(1);
    const totalPages = ref(1);
    const search = ref("");
    const pendingDelete = ref(null);

    const hasMore = computed(() => page.value < totalPages.value);

    async function fetchPage(targetPage) {
        if (!currentItem.value?.id) return;
        const params = new URLSearchParams({ page: String(targetPage) });
        if (search.value) params.set("search", search.value);
        const url = `${buildPath(itemTransactionsPath, { id: currentItem.value.id })}?${params}`;
        const payload = await list.request(url, null, HttpMethod.Get);
        if (!payload || payload.success === false) return;
        const incoming = payload.items ?? [];
        if (targetPage === 1) {
            transactions.value = incoming;
        } else {
            transactions.value.push(...incoming);
        }
        page.value = payload.page ?? targetPage;
        totalPages.value = payload.totalPages ?? 1;
    }

    async function open(item) {
        currentItem.value = item;
        transactions.value = [];
        page.value = 1;
        totalPages.value = 1;
        search.value = "";
        pendingDelete.value = null;
        show.value = true;
        if (!item?.id) return;
        await fetchPage(1);
    }

    function close() {
        show.value = false;
        currentItem.value = null;
        transactions.value = [];
        pendingDelete.value = null;
        page.value = 1;
        totalPages.value = 1;
        search.value = "";
    }

    async function loadMore() {
        if (!hasMore.value || list.loading.value) return;
        await fetchPage(page.value + 1);
    }

    /**
     * Apply a new search term and re-fetch from page 1 — keeps the
     * sentinel-driven pagination consistent. Debouncing happens at the
     * AppSearchInput level, so this is called only when the term has
     * actually settled.
     */
    async function applySearch(value) {
        search.value = value ?? "";
        page.value = 1;
        totalPages.value = 1;
        transactions.value = [];
        await fetchPage(1);
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
        const payload = await del.request(
            buildPath(deleteTransactionPath, { id: tx.id }),
        );
        if (!payload || payload.success === false) return false;
        transactions.value = transactions.value.filter(
            (row) => row.id !== tx.id,
        );
        pendingDelete.value = null;
        toast.success(t("personal_finance.transactions.deleted"));
        return true;
    }

    return {
        show,
        currentItem,
        transactions,
        loading: list.loading,
        hasMore,
        loadMore,
        search,
        applySearch,
        pendingDelete,
        deleteLoading: del.loading,
        open,
        close,
        applyUpdated,
        confirmDelete,
        doDelete,
    };
}
