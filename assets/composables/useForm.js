import { ref } from "vue";

export function useForm() {
    const errors = ref({});

    function validate(checks) {
        const validationErrors = {};
        for (const [field, check] of Object.entries(checks)) {
            const error = check();
            if (error) validationErrors[field] = error;
        }
        errors.value = validationErrors;
        return Object.keys(validationErrors).length === 0;
    }

    function setErrors(newErrors) {
        errors.value = newErrors;
    }

    function clearErrors() {
        errors.value = {};
    }

    return { errors, validate, setErrors, clearErrors };
}
