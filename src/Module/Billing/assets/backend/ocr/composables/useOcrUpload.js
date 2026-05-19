import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

export function useOcrUpload({
    uploadPath,
    jobs,
    previews,
    persistPreviews,
    removePreview,
    startPolling,
}) {
    const { t } = useI18n();
    const uploadingCount = ref(0);

    async function onFileSelected(file) {
        if (!file) return;
        const preview = {
            key: Date.now(),
            url: URL.createObjectURL(file),
            mime: file.type,
            jobId: null,
            isBlob: true,
        };
        previews.value = [...previews.value, preview];
        uploadingCount.value++;
        try {
            const form = new FormData();
            form.append("document", file);
            const res = await fetch(uploadPath, {
                method: HttpMethod.Post,
                body: form,
            });
            const data = await res.json();
            if (!data.success) {
                toast.error(
                    t(
                        data.errors?.document ??
                            data.error ??
                            "shared.common.error",
                    ),
                );
                removePreview(preview.key);
                return;
            }
            toast.success(
                t("backend.billing.ocr.upload.success", { id: data.job.id }),
            );
            jobs.value = [data.job, ...jobs.value].slice(0, 10);
            URL.revokeObjectURL(preview.url);
            previews.value = previews.value.map((p) =>
                p.key === preview.key
                    ? {
                          ...p,
                          url: data.job.mediaUrl,
                          mime: data.job.mediaMime,
                          jobId: data.job.id,
                          isBlob: false,
                      }
                    : p,
            );
            persistPreviews(previews.value);
            startPolling();
        } catch {
            toast.error(t("shared.common.error"));
            removePreview(preview.key);
        } finally {
            uploadingCount.value--;
        }
    }

    return { uploadingCount, onFileSelected };
}
