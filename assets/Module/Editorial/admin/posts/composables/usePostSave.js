import { ref } from "vue";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function usePostSave(createPath, editPath, onSuccess) {
    const { loading, request } = useApiRequest();
    const errors = ref({});
    const conflict = ref(false);

    async function save(postId, formData) {
        errors.value = {};
        conflict.value = false;
        const url = postId ? buildPath(editPath, { id: postId }) : createPath;
        const data = await request(url, formData);
        if (!data) return false;
        if (data.conflict) {
            conflict.value = true;
            return false;
        }
        if (data.success) {
            onSuccess(data.post);
            return true;
        }
        errors.value = data.errors ?? {};
        return false;
    }

    return { loading, errors, conflict, save };
}
