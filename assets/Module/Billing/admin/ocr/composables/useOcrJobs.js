import { onBeforeUnmount, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";

/**
 * One-stop composable for the OCR job list mounted on a Vue ref:
 *   - polling: refreshes every non-terminal job in place every `intervalMs`
 *   - retry:   re-dispatches a (typically Failed) job and restarts polling
 *   - hasInvoice: pure predicate exposed for templates (cleaner than inline)
 *
 * Cleanup is automatic on `onBeforeUnmount`. Call `start()` again after
 * pushing fresh jobs into the ref (upload, page change, retry…) to pick up
 * the new ones.
 *
 * Endpoints expected (all return `{success, job}`):
 *   - statusUrlTemplate  : '/admin/billing/ocr/jobs/__id__/status' (GET)
 *   - retryUrlTemplate   : '/admin/billing/ocr/jobs/__id__/retry'  (POST)
 */
export function useOcrJobs(
    jobsRef,
    {
        statusUrlTemplate,
        retryUrlTemplate,
        validatePathTemplate,
        intervalMs = 4000,
    } = {},
) {
    const { t } = useI18n();
    const { request } = useApiRequest();
    const pendingValidate = ref(null); // job pending confirmation
    const validating = ref(null); // job id currently being validated

    let pollers = [];

    function pollOne(jobId) {
        const url = buildPath(statusUrlTemplate, { id: jobId });
        let cancelled = false;

        const tick = async () => {
            if (cancelled) return;
            let job = null;
            try {
                const res = await fetch(url, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data?.success && data.job) job = data.job;
                }
            } catch {
                /* swallow transient errors — we'll retry on next tick */
            }

            if (job) {
                const idx = jobsRef.value.findIndex((j) => j.id === jobId);
                if (idx !== -1) jobsRef.value[idx] = job;
                if (job.isTerminal) return;
            }
            if (!cancelled) setTimeout(tick, intervalMs);
        };

        setTimeout(tick, intervalMs);
        return () => {
            cancelled = true;
        };
    }

    function stop() {
        pollers.forEach((cancel) => cancel?.());
        pollers = [];
    }

    function start() {
        stop();
        pollers = (jobsRef.value ?? [])
            .filter((j) => !j.isTerminal)
            .map((j) => pollOne(j.id));
    }

    /**
     * Re-dispatch a (typically failed) job and restart polling so the user
     * sees the status flip back through Queued → Extracting → … live.
     */
    async function retry(job) {
        if (!retryUrlTemplate) {
            throw new Error(
                "useOcrJobs: retryUrlTemplate is required to call retry()",
            );
        }
        const data = await request(buildPath(retryUrlTemplate, { id: job.id }));
        if (!data?.success) {
            toast.error(t(data?.error ?? "shared.common.error"));
            return false;
        }
        const idx = jobsRef.value.findIndex((j) => j.id === job.id);
        if (idx !== -1) jobsRef.value[idx] = data.job;
        toast.success(t("backend.billing.ocr.retryQueued"));
        start();
        return true;
    }

    /**
     * Validate the invoice linked to a completed OCR job.
     * Call confirmValidate() from the modal's confirm button.
     */
    async function confirmValidate() {
        const job = pendingValidate.value;
        if (!job || !validatePathTemplate || !job.invoiceId) return;
        validating.value = job.id;
        const data = await request(
            buildPath(validatePathTemplate, { id: job.invoiceId }),
        );
        validating.value = null;
        pendingValidate.value = null;
        if (!data?.success) {
            toast.error(t(data?.error ?? "shared.common.error"));
            return;
        }
        const idx = jobsRef.value.findIndex((j) => j.id === job.id);
        if (idx !== -1) {
            jobsRef.value[idx] = {
                ...jobsRef.value[idx],
                invoiceCanValidate: false,
                invoiceStatus: "validated",
            };
        }
        toast.success(t("backend.billing.invoices.show.validated"));
    }

    /**
     * True when the job has a linked invoice — either already completed or
     * being re-scanned (invoiceId is set even when status is back to queued).
     */
    function hasInvoice(job) {
        return job.invoiceId != null;
    }

    onBeforeUnmount(stop);

    return {
        start,
        stop,
        retry,
        pendingValidate,
        confirmValidate,
        validating,
        hasInvoice,
    };
}
