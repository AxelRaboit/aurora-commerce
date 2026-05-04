import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useInvoiceCreditNote(
    creditNotePath,
    showPath,
    invoice,
    submit,
) {
    const showCreditNoteModal = ref(false);
    const creditNoteReason = ref("");
    const creatingCreditNote = ref(false);

    async function createCreditNote() {
        creatingCreditNote.value = true;
        const data = await submit(
            creditNotePath,
            { reason: creditNoteReason.value || null },
            { successMessage: "admin.billing.invoices.show.creditNoteCreated" },
        );
        creatingCreditNote.value = false;
        if (data) {
            showCreditNoteModal.value = false;
            invoice.value = data.invoice;
            window.location.href = buildPath(showPath, {
                id: data.creditNote.id,
            });
        }
    }

    return {
        showCreditNoteModal,
        creditNoteReason,
        creatingCreditNote,
        createCreditNote,
    };
}
