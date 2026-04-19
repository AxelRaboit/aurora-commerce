import { ref } from "vue";

export function usePostSave(createPath, editPath, onSuccess) {
    const loading = ref(false);
    const errors = ref({});

    async function save(postId, formData) {
        if (loading.value) return false;
        loading.value = true;
        errors.value = {};
        try {
            const url = postId
                ? editPath.replace("__id__", postId)
                : createPath;
            const response = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(formData),
            });
            if (!response.ok && response.status !== 422)
                throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (data.success) {
                onSuccess(data.post);
                return true;
            }
            errors.value = data.errors ?? {};
        } catch (error) {
            errors.value = { network: error.message };
        } finally {
            loading.value = false;
        }
        return false;
    }

    return { loading, errors, save };
}
