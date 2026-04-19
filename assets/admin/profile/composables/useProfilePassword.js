import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useForm } from "@/composables/useForm.js";
import { required } from "@/utils/validators.js";
import { passwordValidator } from "@/utils/passwordRules.js";

export function useProfilePassword(passwordPath) {
    const { t: translate } = useI18n();
    const { errors: passwordErrors, validate: validatePassword, setErrors: setPasswordErrors, clearErrors: clearPasswordErrors } = useForm();

    const currentPassword = ref("");
    const newPassword = ref("");
    const confirmPassword = ref("");
    const passwordLoading = ref(false);

    async function savePassword() {
        const isValid = validatePassword({
            current_password: () => required(translate("profile.errors.current_password_invalid"))(currentPassword.value),
            password: () => passwordValidator(translate)(newPassword.value),
            password_confirmation: () => {
                if (newPassword.value && newPassword.value !== confirmPassword.value) return translate("profile.errors.password_mismatch");
                return null;
            },
        });

        if (!isValid) return;

        passwordLoading.value = true;
        try {
            const response = await fetch(passwordPath, {
                method: "POST",
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
                toast.success(translate("profile.password.saved"));
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

    return { currentPassword, newPassword, confirmPassword, passwordLoading, passwordErrors, savePassword };
}
