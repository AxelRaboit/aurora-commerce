import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useForm } from "@/composables/useForm.js";

export function useTagCreate(createPath, onSuccess) {
    const { t: translate } = useI18n();
    const { errors, setErrors, clearErrors } = useForm();

    const showModal = ref(false);
    const name = ref("");
    const loading = ref(false);

    function open() {
        name.value = "";
        clearErrors();
        showModal.value = true;
    }

    async function submit() {
        if (loading.value) return;
        loading.value = true;
        try {
            const response = await fetch(createPath, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ name: name.value }),
            });
            const data = await response.json();
            if (data.success) {
                showModal.value = false;
                toast.success(translate("admin.tags.created"));
                onSuccess(data.tag);
            } else {
                setErrors(data.errors ?? {});
            }
        } finally {
            loading.value = false;
        }
    }

    return { showModal, name, loading, errors, open, submit };
}
