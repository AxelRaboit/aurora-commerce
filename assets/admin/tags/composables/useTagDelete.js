import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

export function useTagDelete(deletePath, onSuccess) {
    const { t: translate } = useI18n();
    const pendingDelete = ref(null);
    const loading = ref(false);

    function confirm(tag) {
        pendingDelete.value = tag;
    }

    async function submit() {
        if (loading.value || !pendingDelete.value) return;
        loading.value = true;
        try {
            const url = deletePath.replace("__id__", pendingDelete.value.id);
            const response = await fetch(url, { method: "POST" });
            const data = await response.json();
            if (data.success) {
                const id = pendingDelete.value.id;
                pendingDelete.value = null;
                toast.success(translate("admin.tags.deleted"));
                onSuccess(id);
            }
        } finally {
            loading.value = false;
        }
    }

    return { pendingDelete, loading, confirm, submit };
}
