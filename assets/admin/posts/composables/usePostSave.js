import { ref } from "vue";
import { useApiRequest } from "@/composables/useApiRequest.js";

export function usePostSave(createPath, editPath, onSuccess) {
    const { loading, request } = useApiRequest();
    const errors = ref({});

    async function save(postId, formData) {
        errors.value = {};
        const url = postId ? editPath.replace("__id__", postId) : createPath;
        const data = await request(url, formData);
        if (!data) return false;
        if (data.success) {
            onSuccess(data.post);
            return true;
        }
        errors.value = data.errors ?? {};
        return false;
    }

    return { loading, errors, save };
}
