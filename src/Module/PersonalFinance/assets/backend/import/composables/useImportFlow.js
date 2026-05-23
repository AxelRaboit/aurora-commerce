import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Drives the 3-step import flow:
 *   upload  → preview  → done
 *
 * `preview` is the parsed-but-not-persisted state. `done` carries the
 * server-returned report (created/skipped counts + list of new
 * categories). The composable keeps the rows in memory between
 * preview and process so the user can audit before confirming
 * without re-uploading the file.
 */
export function useImportFlow({ previewPath, processPath }) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    const step = ref("upload"); // upload | preview | done
    const walletId = ref(null);
    const file = ref(null);
    const preview = ref(null);
    const rowsForProcess = ref([]);
    const report = ref(null);
    const fileError = ref(null);

    const canConfirm = computed(() => preview.value?.validCount > 0);

    function reset() {
        step.value = "upload";
        file.value = null;
        preview.value = null;
        rowsForProcess.value = [];
        report.value = null;
        fileError.value = null;
    }

    async function submitUpload() {
        if (!walletId.value) {
            fileError.value = t("personal_finance.import.errors.wallet_required");
            return;
        }
        if (!file.value) {
            fileError.value = t("personal_finance.import.errors.file_required");
            return;
        }
        fileError.value = null;

        const formData = new FormData();
        formData.append("file", file.value);

        const response = await request(
            buildPath(previewPath, { walletId: walletId.value }),
            null,
            { method: "POST", rawBody: formData },
        );
        if (!response) return;
        if (response.success === false) {
            fileError.value = response.errors?.file ?? t("shared.common.error");
            return;
        }

        preview.value = response.preview;
        rowsForProcess.value = response.rowsForProcess ?? [];
        step.value = "preview";
    }

    async function confirmProcess() {
        if (!walletId.value || !rowsForProcess.value.length) return;

        const response = await request(
            buildPath(processPath, { walletId: walletId.value }),
            { rows: rowsForProcess.value },
        );
        if (!response) return;
        if (response.success === false) {
            toast.error(t("shared.common.error"));
            return;
        }

        report.value = {
            createdCount: response.createdCount ?? 0,
            skippedCount: response.skippedCount ?? 0,
            categoriesCreated: response.categoriesCreated ?? [],
            skippedRows: response.skippedRows ?? [],
        };
        step.value = "done";
        toast.success(t("personal_finance.import.completed", { count: report.value.createdCount }));
    }

    return {
        step, walletId, file, preview, report, fileError, canConfirm,
        loading,
        submitUpload, confirmProcess, reset,
    };
}
