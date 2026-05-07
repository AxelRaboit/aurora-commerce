import { ref, watch, onMounted, onUnmounted } from "vue";
import { OcrJobStatus } from "@billing/utils/ocrJobStatus.js";

const PREVIEW_STORE_KEY = "aurora-ocr-previews";

export function useOcrPreviews(jobs) {
    const previews = ref([]);
    const scanFlashes = ref({});
    const flashedJobIds = new Set();

    function loadPersistedPreviews() {
        try {
            return JSON.parse(localStorage.getItem(PREVIEW_STORE_KEY) ?? "[]");
        } catch {
            return [];
        }
    }

    function persistPreviews(list) {
        const toStore = list
            .filter((p) => p.jobId !== null && !p.isBlob)
            .map(({ jobId, url, mime }) => ({ jobId, url, mime }));
        localStorage.setItem(PREVIEW_STORE_KEY, JSON.stringify(toStore));
    }

    function getPreviewJob(jobId) {
        return jobs.value.find((j) => j.id === jobId) ?? null;
    }
    function isJobScanning(jobId) {
        const job = getPreviewJob(jobId);
        return job !== null && !job.isTerminal;
    }

    function removePreview(key) {
        const p = previews.value.find((p) => p.key === key);
        if (p?.isBlob) URL.revokeObjectURL(p.url);
        previews.value = previews.value.filter((p) => p.key !== key);
        persistPreviews(previews.value);
    }

    watch(
        jobs,
        (newJobs) => {
            for (const preview of previews.value) {
                if (!preview.jobId || flashedJobIds.has(preview.jobId))
                    continue;
                const job = newJobs.find((j) => j.id === preview.jobId);
                if (job?.isTerminal) {
                    flashedJobIds.add(preview.jobId);
                    const id = preview.jobId;
                    scanFlashes.value = {
                        ...scanFlashes.value,
                        [id]:
                            job.status === OcrJobStatus.Failed
                                ? "error"
                                : "success",
                    };
                    setTimeout(() => {
                        const { [id]: _, ...rest } = scanFlashes.value;
                        scanFlashes.value = rest;
                        localStorage.setItem(
                            PREVIEW_STORE_KEY,
                            JSON.stringify(
                                loadPersistedPreviews().filter(
                                    (p) => p.jobId !== id,
                                ),
                            ),
                        );
                    }, 1200);
                }
            }
        },
        { deep: true },
    );

    onMounted(() => {
        const stored = loadPersistedPreviews();
        const activeJobIds = new Set(
            jobs.value.filter((j) => !j.isTerminal).map((j) => j.id),
        );
        const restored = stored
            .filter(({ jobId }) => activeJobIds.has(jobId))
            .map(({ jobId, url, mime }) => ({
                key: jobId,
                url,
                mime,
                jobId,
                isBlob: false,
            }));
        if (restored.length) previews.value = restored;
        localStorage.setItem(
            PREVIEW_STORE_KEY,
            JSON.stringify(
                stored.filter(({ jobId }) => activeJobIds.has(jobId)),
            ),
        );
    });

    onUnmounted(() => {
        for (const p of previews.value)
            if (p.isBlob) URL.revokeObjectURL(p.url);
    });

    return {
        previews,
        scanFlashes,
        getPreviewJob,
        isJobScanning,
        removePreview,
        persistPreviews,
    };
}
