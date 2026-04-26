import { HttpMethod } from "@/shared/utils/httpMethod.js";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useForm } from "@/shared/composables/useForm.js";
import { required } from "@/shared/utils/validators.js";
import { passwordValidator } from "@/shared/utils/passwordRules.js";

export function useProfilePassword(passwordPath) {
    const { t } = useI18n();
    const {
        errors: passwordErrors,
        validate: validatePassword,
        setErrors: setPasswordErrors,
        clearErrors: clearPasswordErrors,
    } = useForm();

    const currentPassword = ref("");
    const newPassword = ref("");
    const confirmPassword = ref("");
    const passwordLoading = ref(false);

    async function savePassword() {
        const isValid = validatePassword({
            current_password: () =>
                required(t("admin.profile.errors.current_password_invalid"))(
                    currentPassword.value,
                ),
            password: () => passwordValidator(t)(newPassword.value),
            password_confirmation: () => {
                if (
                    newPassword.value &&
                    newPassword.value !== confirmPassword.value
                )
                    return t("admin.profile.errors.password_mismatch");
                return null;
            },
        });

        if (!isValid) return;

        passwordLoading.value = true;
        try {
            const response = await fetch(passwordPath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    current_password: currentPassword.value,
                    password: newPassword.value,
                    password_confirmation: confirmPassword.value,
                }),
            });
            const data = await response.json();
            if (data.success) {
                clearPasswordErrors();
                toast.success(t("admin.profile.password.saved"));
                currentPassword.value = "";
                newPassword.value = "";
                confirmPassword.value = "";
            } else {
                setPasswordErrors(data.errors ?? {});
            }
        } finally {
            passwordLoading.value = false;
        }
    }

    return {
        currentPassword,
        newPassword,
        confirmPassword,
        passwordLoading,
        passwordErrors,
        savePassword,
    };
}
