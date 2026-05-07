import { ref, computed } from "vue";

export function useInvoiceState(initialInvoice) {
    const invoice = ref({ ...initialInvoice });

    const validating = ref(false);
    const deleting = ref(false);
    const showDeleteModal = ref(false);
    const deleteTiersToo = ref(false);
    const deleteBuyerToo = ref(false);

    const canDeleteTiers = computed(
        () =>
            invoice.value.supplier !== null &&
            invoice.value.supplierInvoiceCount === 1,
    );
    const canDeleteBuyer = computed(
        () =>
            invoice.value.buyer !== null &&
            invoice.value.buyerInvoiceCount === 1,
    );
    const isNeedsReview = computed(
        () => invoice.value.status === "needs_review",
    );
    const isLocked = computed(
        () => !["draft", "needs_review"].includes(invoice.value.status),
    );
    const isCreditNote = computed(() => invoice.value.isCreditNote);
    const isCancelled = computed(() => invoice.value.isCancelled);
    const canHaveCreditNote = computed(
        () =>
            ["validated", "paid"].includes(invoice.value.status) &&
            !isCancelled.value,
    );

    return {
        invoice,
        validating,
        deleting,
        showDeleteModal,
        deleteTiersToo,
        deleteBuyerToo,
        canDeleteTiers,
        canDeleteBuyer,
        isNeedsReview,
        isLocked,
        isCreditNote,
        isCancelled,
        canHaveCreditNote,
    };
}
