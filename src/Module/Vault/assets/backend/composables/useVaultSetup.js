import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRequest } from "@shared/composables/http/backend/useRequest.js";
import { useForm } from "@shared/composables/form/useForm.js";
import { required } from "@shared/utils/validation/validators.js";
import { generateSalt } from "@vault/backend/composables/useVaultCrypto.js";

/**
 * Handles vault initialisation: password form state, validation, salt generation
 * and API call. Returns the setup payload on success, null otherwise.
 *
 * @param {string} setupPath
 */
export function useVaultSetup(setupPath) {
    const { t } = useI18n();
    const masterPassword = ref("");
    const confirmPassword = ref("");
    const { loading, request } = useRequest();
    const { errors, validate, clearErrors } = useForm();

    async function submit(keepUnlocked, keepDuration) {
        const valid = validate({
            masterPassword: () =>
                required(t("vault.setup.errors.password_required"))(
                    masterPassword.value,
                ),
            confirmPassword: () => {
                const requiredError = required(
                    t("vault.setup.errors.password_required"),
                )(confirmPassword.value);
                if (requiredError) return requiredError;
                return masterPassword.value !== confirmPassword.value
                    ? t("vault.setup.errors.passwords_mismatch")
                    : null;
            },
        });
        if (!valid) return null;

        const salt = generateSalt();
        const data = await request(setupPath, { argon2Salt: salt });
        if (!data?.success) return null;

        clearErrors();
        return {
            salt,
            masterPassword: masterPassword.value,
            config: data.config,
            keepUnlocked,
            keepDuration,
        };
    }

    return { masterPassword, confirmPassword, errors, loading, submit };
}
