import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

export function useMediaPickerUpload({
    uploadPath,
    imagesOnly,
    items,
    currentFolderId,
    selected,
}) {
    const { t } = useI18n();
    const fileInputRef = ref(null);
    const uploading = ref(false);
    const dragOver = ref(false);

    async function uploadFiles(files) {
        if (!files?.length) return;
        uploading.value = true;
        let lastUploaded = null;
        try {
            for (const file of files) {
                const body = new FormData();
                body.append("image", file);
                if (currentFolderId.value)
                    body.append("folderId", String(currentFolderId.value));
                const response = await fetch(uploadPath, {
                    method: HttpMethod.Post,
                    body,
                });
                if (!response.ok) throw new Error();
                const data = await response.json();
                if (data.success && data.media) {
                    items.value.unshift(data.media);
                    lastUploaded = data.media;
                }
            }
            if (lastUploaded && (!imagesOnly || lastUploaded.isImage))
                selected.value = lastUploaded;
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            uploading.value = false;
        }
    }

    function onDragOver(event) {
        if (!event.dataTransfer.types.includes("Files")) return;
        event.preventDefault();
        dragOver.value = true;
    }
    function onDragLeave(event) {
        if (!event.currentTarget.contains(event.relatedTarget))
            dragOver.value = false;
    }
    function onDrop(event) {
        if (!event.dataTransfer.types.includes("Files")) return;
        event.preventDefault();
        dragOver.value = false;
        uploadFiles(Array.from(event.dataTransfer.files ?? []));
    }

    return {
        fileInputRef,
        uploading,
        dragOver,
        uploadFiles,
        onDragOver,
        onDragLeave,
        onDrop,
    };
}
