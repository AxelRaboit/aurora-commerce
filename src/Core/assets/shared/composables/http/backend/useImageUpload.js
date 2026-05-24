import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * Upload an image file to the media endpoint.
 *
 * Usage:
 *   const { uploading, inputRef, uploadFromEvent, reset } = useImageUpload({
 *       onSuccess: ({ file, media }) => { ... },
 *       onError: () => toast.error(...),
 *   });
 *
 * The returned `inputRef` should be bound to a hidden <input type="file"> so its
 * value can be cleared after upload (allows selecting the same file again).
 */
export function useImageUpload({
    onSuccess,
    onError,
    endpoint = "/backend/media/upload",
} = {}) {
    const uploading = ref(false);
    const inputRef = ref(null);

    async function uploadFromEvent(event) {
        const file = event.target.files?.[0];
        if (!file) return;
        uploading.value = true;
        try {
            const body = new FormData();
            body.append("image", file);
            const response = await fetch(endpoint, {
                method: HttpMethod.Post,
                body,
            });
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (data.success) onSuccess?.(data);
        } catch (err) {
            onError?.(err);
        } finally {
            uploading.value = false;
            if (inputRef.value?.reset) {
                inputRef.value.reset();
            } else if (inputRef.value) {
                inputRef.value.value = "";
            }
        }
    }

    return { uploading, inputRef, uploadFromEvent };
}
