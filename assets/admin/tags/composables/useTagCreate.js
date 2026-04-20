import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useForm } from "@/composables/useForm.js";
import { useApiRequest } from "@/composables/useApiRequest.js";

export function useTagCreate(createPath, onSuccess) {
    const { t } = useI18n();
    const { errors, setErrors, clearErrors } = useForm();
    const { loading, request } = useApiRequest();

    const showModal = ref(false);
    const name = ref("");

    function open() {
        name.value = "";
        clearErrors();
        showModal.value = true;
    }

    async function submit() {
        const data = await request(createPath, { name: name.value });
        if (!data) return;
        if (data.success) {
            showModal.value = false;
            toast.success(t("admin.tags.created"));
            onSuccess(data.tag);
        } else {
            setErrors(data.errors ?? {});
        }
    }

    return { showModal, name, loading, errors, open, submit };
}
