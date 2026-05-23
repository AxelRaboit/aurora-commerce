import { ref } from "vue";

/**
 * Wires the quick-add transaction modal launched from each budget item
 * row. Owns the modal ref and computes the prefill payload from the
 * row + section currently being looked at.
 *
 * @param {object}  opts
 * @param {import("vue").Ref<number|null>} opts.selectedWalletId - which wallet the budget UI is showing
 * @param {import("vue").Ref<string>}      opts.currentMonth     - YYYY-MM currently displayed
 */
export function useBudgetQuickAdd({ selectedWalletId, currentMonth }) {
    const createModalRef = ref(null);

    /**
     * Map a budget section to the matching transaction type. Income →
     * income, everything else (Bills/Expenses/Savings/Debt) → expense.
     */
    function sectionToType(section) {
        return section === "income" ? "income" : "expense";
    }

    /**
     * Default date for the quick-add modal: today if we're viewing the
     * current month, otherwise the 15th of the budgeted month so the
     * transaction lands in the period the user is looking at.
     */
    function defaultDateForCurrentView() {
        const now = new Date();
        const viewedMonth = currentMonth.value;
        const viewedKey = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
        if (viewedMonth === viewedKey) {
            return now.toISOString().slice(0, 10);
        }
        return `${viewedMonth}-15`;
    }

    function onQuickAdd(item, section) {
        if (!selectedWalletId.value) return;
        createModalRef.value?.open(selectedWalletId.value, {
            type: sectionToType(section),
            categoryId: item.categoryId ?? null,
            date: defaultDateForCurrentView(),
            description: item.label ?? "",
        });
    }

    return { createModalRef, onQuickAdd };
}
