import { useI18n } from "vue-i18n";
import { useForm } from "@/shared/composables/useForm.js";

/**
 * Wraps useForm with the patterns specific to the front auth flows :
 *  - server-side errors (translation keys) hydrated from props
 *  - native form submit when client-side validation passes
 */
export function useAuthForm(initialErrors = {}) {
    const { t } = useI18n();
    const { errors, validate, setErrors, clearErrors } = useForm();

    if (initialErrors && Object.keys(initialErrors).length > 0) {
        const translated = {};
        for (const [key, value] of Object.entries(initialErrors)) {
            translated[key] = typeof value === "string" ? t(value) : value;
        }
        setErrors(translated);
    }

    function submitOnValid(event, validators) {
        if (validate(validators)) {
            event.target.submit();
        }
    }

    return { errors, validate, setErrors, clearErrors, submitOnValid };
}
