import { useI18n } from "vue-i18n";

export function useGenerateInvoice(generateInvoicePath, activeProject) {
    const { t } = useI18n();

    async function generateInvoice() {
        if (!activeProject.value || !generateInvoicePath) return;
        if (!confirm(t("backend.projects.confirmGenerateInvoice"))) return;
        const url = generateInvoicePath.replace(
            "__id__",
            activeProject.value.id,
        );
        try {
            const response = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
            });
            const data = await response.json();
            if (data.success && data.invoiceId) {
                window.location.href = `/backend/billing/invoices/${data.invoiceId}`;
            }
        } catch {
            // silent — redirect on success only
        }
    }

    return { generateInvoice };
}
