import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useForm } from "@/composables/useForm.js";

export function useTagEdit(editPath, onSuccess) {
    const { t: translate } = useI18n();
    const { errors, setErrors, clearErrors } = useForm();

    const editingTag = ref(null);
    const name = ref("");
    const loading = ref(false);

    function open(tag) {
        editingTag.value = tag;
        name.value = tag.name;
        clearErrors();
    }

    async function submit() {
        if (loading.value || !editingTag.value) return;
        loading.value = true;
        try {
            const url = editPath.replace("__id__", editingTag.value.id);
            const response = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ name: name.value }),
            });
            const data = await response.json();
            if (data.success) {
                editingTag.value = null;
                toast.success(translate("admin.tags.updated"));
                onSuccess(data.tag);
            } else {
                setErrors(data.errors ?? {});
            }
        } finally {
            loading.value = false;
        }
    }

    return { editingTag, name, loading, errors, open, submit };
}
