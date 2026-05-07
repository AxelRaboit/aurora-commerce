import { ref, computed, watch } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

export function useOcrDelete({ deletePath, jobs, previews, removePreview }) {
    const { t } = useI18n();
    const { request } = useApiRequest();

    const pendingDelete = ref(null);
    const deleteLoading = ref(false);
    const deleteTiersToo = ref(false);
    const logsJob = ref(null);

    const canDeleteTiers = computed(
        () => !!pendingDelete.value?.invoiceCanDeleteTiers,
    );

    watch(
        jobs,
        (newJobs) => {
            if (logsJob.value) {
                const updated = newJobs.find((j) => j.id === logsJob.value.id);
                if (updated) logsJob.value = updated;
            }
        },
        { deep: true },
    );

    async function doDelete() {
        if (deleteLoading.value || !pendingDelete.value) return;
        deleteLoading.value = true;
        const data = await request(
            buildPath(deletePath, { id: pendingDelete.value.id }),
            { deleteTiers: deleteTiersToo.value },
        );
        deleteLoading.value = false;
        if (!data?.success) {
            toast.error(t(data?.error ?? "shared.common.error"));
            return;
        }
        const deletedId = pendingDelete.value.id;
        jobs.value = jobs.value.filter((j) => j.id !== deletedId);
        const linked = previews.value.find((p) => p.jobId === deletedId);
        if (linked) removePreview(linked.key);
        pendingDelete.value = null;
        deleteTiersToo.value = false;
        toast.success(t("backend.billing.ocr.deleted"));
    }

    return {
        pendingDelete,
        deleteLoading,
        deleteTiersToo,
        canDeleteTiers,
        logsJob,
        doDelete,
    };
}
