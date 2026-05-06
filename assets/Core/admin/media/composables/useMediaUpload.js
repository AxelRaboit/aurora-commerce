import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function useMediaUpload(props, media, currentFolderId) {
    const { t } = useI18n();

    const uploadInput = ref(null);
    const uploading = ref(false);
    const uploadProgress = ref([]);
    const filesDragOver = ref(false);

    function uploadWithProgress(url, formData, onProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable)
                    onProgress(Math.round((e.loaded / e.total) * 100));
            };
            xhr.onload = () => {
                try {
                    resolve(JSON.parse(xhr.responseText));
                } catch {
                    reject();
                }
            };
            xhr.onerror = reject;
            xhr.open("POST", url);
            xhr.send(formData);
        });
    }

    async function uploadFileList(files) {
        if (!files.length) return;
        uploading.value = true;
        uploadProgress.value = files.map((f) => ({ name: f.name, percent: 0 }));
        try {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const body = new FormData();
                body.append("image", file);
                if (currentFolderId.value)
                    body.append("folderId", String(currentFolderId.value));
                const data = await uploadWithProgress(
                    props.uploadPath,
                    body,
                    (p) => {
                        uploadProgress.value[i].percent = p;
                    },
                );
                if (data.media) media.value.unshift(data.media);
            }
            toast.success(t("backend.media.uploaded", { count: files.length }));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            uploading.value = false;
            uploadProgress.value = [];
            if (uploadInput.value) uploadInput.value.value = "";
        }
    }

    async function uploadFiles(event) {
        await uploadFileList(Array.from(event.target.files ?? []));
    }

    function onMainDragOver(event) {
        if (event.dataTransfer.types.includes("Files")) {
            event.preventDefault();
            filesDragOver.value = true;
        }
    }

    function onMainDragLeave(event) {
        if (!event.currentTarget.contains(event.relatedTarget))
            filesDragOver.value = false;
    }

    async function onMainDrop(event) {
        if (!event.dataTransfer.types.includes("Files")) return;
        event.preventDefault();
        filesDragOver.value = false;
        await uploadFileList(Array.from(event.dataTransfer.files));
    }

    return {
        uploadInput,
        uploading,
        uploadProgress,
        filesDragOver,
        uploadFiles,
        onMainDragOver,
        onMainDragLeave,
        onMainDrop,
    };
}
