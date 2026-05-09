import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useForm } from "@shared/composables/form/useForm.js";
import { required } from "@shared/utils/validation/validators.js";

export function useVaultUnlock() {
    const { t } = useI18n();
    const masterPassword = ref("");
    const loading = ref(false);
    const { errors, validate, setErrors, clearErrors } = useForm();

    /**
     * Validates the form and returns the unlock payload to emit, or null if invalid.
     * The payload includes onError/onSuccess callbacks that manage the loading state.
     */
    function buildPayload(keepUnlocked, keepDuration) {
        if (
            !validate({
                masterPassword: () =>
                    required(t("vault.setup.errors.password_required"))(
                        masterPassword.value,
                    ),
            })
        ) {
            return null;
        }

        loading.value = true;
        clearErrors();

        return {
            masterPassword: masterPassword.value,
            keepUnlocked,
            keepDuration,
            onError: () => {
                loading.value = false;
                setErrors({ masterPassword: t("vault.unlock.error") });
            },
            onSuccess: () => {
                loading.value = false;
            },
        };
    }

    return { masterPassword, errors, loading, buildPayload };
}
