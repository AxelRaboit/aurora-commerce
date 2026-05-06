import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useForm } from "@/shared/composables/form/useForm.js";
import {
    required,
    email,
    compose,
} from "@/shared/utils/validation/validators.js";

export function useProfileInfo(updatePath, initialName, initialEmail) {
    const { t } = useI18n();
    const {
        errors: infoErrors,
        validate: validateInfo,
        setErrors: setInfoErrors,
        clearErrors: clearInfoErrors,
    } = useForm();

    const infoName = ref(initialName);
    const infoEmail = ref(initialEmail);
    const infoLoading = ref(false);

    async function saveInfo() {
        const isValid = validateInfo({
            name: () =>
                required(t("backend.profile.errors.name_required"))(
                    infoName.value,
                ),
            email: () =>
                compose(
                    required(t("backend.profile.errors.email_invalid")),
                    email(t("backend.profile.errors.email_invalid")),
                )(infoEmail.value),
        });

        if (!isValid) return;

        infoLoading.value = true;
        try {
            const response = await fetch(updatePath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    name: infoName.value,
                    email: infoEmail.value,
                }),
            });
            const data = await response.json();
            if (data.success) {
                clearInfoErrors();
                toast.success(t("backend.profile.info.saved"));
            } else {
                setInfoErrors(data.errors ?? {});
            }
        } finally {
            infoLoading.value = false;
        }
    }

    return { infoName, infoEmail, infoLoading, infoErrors, saveInfo };
}
