import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useInlineEdit } from "@/shared/composables/form/useInlineEdit.js";

export function useInvoiceActions(
    props,
    invoice,
    validating,
    deleting,
    showDeleteModal,
    deleteTiersToo,
    deleteBuyerToo,
) {
    const { saveField, submit } = useInlineEdit();

    async function updateField(field, value) {
        const data = await saveField(props.updatePath, field, value);
        if (data) invoice.value = data.invoice;
    }

    async function updateSupplierField(field, value) {
        const supplierId = invoice.value.supplierFull?.id;
        if (!supplierId) return;
        const data = await saveField(
            buildPath(props.tiersUpdatePathTemplate, { id: supplierId }),
            field,
            value,
        );
        if (data)
            invoice.value = {
                ...invoice.value,
                supplierFull: data.tiers,
                supplier: { id: data.tiers.id, name: data.tiers.name },
            };
    }

    async function updateBuyerField(field, value) {
        const buyerId = invoice.value.buyer?.id;
        if (!buyerId) return;
        const data = await saveField(
            buildPath(props.tiersUpdatePathTemplate, { id: buyerId }),
            field,
            value,
        );
        if (data) invoice.value = { ...invoice.value, buyer: data.tiers };
    }

    async function validateInvoice() {
        if (validating.value) return;
        validating.value = true;
        const data = await submit(props.validatePath, null, {
            successMessage: "backend.billing.invoices.show.validated",
        });
        if (data) invoice.value = data.invoice;
        validating.value = false;
    }

    async function deleteInvoice() {
        if (deleting.value) return;
        deleting.value = true;
        const data = await submit(
            props.deletePath,
            {
                deleteTiers: deleteTiersToo.value,
                deleteBuyer: deleteBuyerToo.value,
            },
            { successMessage: "backend.billing.invoices.deleted" },
        );
        deleting.value = false;
        // Always redirect: even when submit() returns null (post-flush cleanup failed
        // server-side), the invoice is already removed from the DB. Staying on the
        // show page would leave the user on a dead URL.
        window.location.replace(props.listPath);
    }

    async function addLine() {
        const data = await submit(props.lineCreatePath, null, {
            successMessage: "backend.billing.invoices.show.line_added",
        });
        if (data) invoice.value = data.invoice;
    }

    async function updateLineField(lineId, field, value) {
        const data = await saveField(
            buildPath(props.lineUpdatePathTemplate, { lineId }),
            field,
            value,
        );
        if (data) invoice.value = data.invoice;
    }

    async function deleteLine(lineId) {
        const data = await submit(
            buildPath(props.lineDeletePathTemplate, { lineId }),
            null,
            { successMessage: "backend.billing.invoices.show.line_deleted" },
        );
        if (data) invoice.value = data.invoice;
    }

    return {
        updateField,
        updateSupplierField,
        updateBuyerField,
        validateInvoice,
        deleteInvoice,
        addLine,
        updateLineField,
        deleteLine,
        submit,
    };
}
