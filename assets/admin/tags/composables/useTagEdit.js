import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useForm } from "@/composables/useForm.js";
import { useApiRequest } from "@/composables/useApiRequest.js";

export function useTagEdit(editPath, onSuccess) {
    const { t } = useI18n();
    const { errors, setErrors, clearErrors } = useForm();
    const { loading, request } = useApiRequest();

    const editingTag = ref(null);
    const name = ref("");

    function open(tag) {
        editingTag.value = tag;
        name.value = tag.name;
        clearErrors();
    }

    async function submit() {
        if (!editingTag.value) return;
        const url = editPath.replace("__id__", editingTag.value.id);
        const data = await request(url, { name: name.value });
        if (!data) return;
        if (data.success) {
            editingTag.value = null;
            toast.success(t("admin.tags.updated"));
            onSuccess(data.tag);
        } else {
            setErrors(data.errors ?? {});
        }
    }

    return { editingTag, name, loading, errors, open, submit };
}
