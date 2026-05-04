import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import {
    required,
    email as emailValidator,
} from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

export function emptyContactForm() {
    return {
        firstName: "",
        lastName: "",
        email: "",
        phone: "",
        company: "",
        notes: "",
    };
}

export function useContactsCreate(createPath, reset) {
    const { t } = useI18n();

    const showCreate = ref(false);
    const newContact = ref(emptyContactForm());
    const {
        errors: createErrors,
        validate,
        clearErrors,
        setErrors,
    } = useForm();
    const { loading: createLoading, request } = useApiRequest();

    function openCreate() {
        newContact.value = emptyContactForm();
        clearErrors();
        showCreate.value = true;
    }

    async function submitCreate() {
        if (
            !validate({
                firstName: () =>
                    required(
                        t("admin.crm.contacts.errors.first_name_required"),
                    )(newContact.value.firstName),
                lastName: () =>
                    required(t("admin.crm.contacts.errors.last_name_required"))(
                        newContact.value.lastName,
                    ),
                email: () =>
                    newContact.value.email
                        ? emailValidator(
                              t("admin.crm.contacts.errors.email_invalid"),
                          )(newContact.value.email)
                        : null,
            })
        )
            return;
        const data = await request(createPath, newContact.value);
        if (!data) return;
        if (data.success) {
            showCreate.value = false;
            toast.success(t("admin.crm.contacts.created"));
            reset();
        } else setErrors(translateServerErrors(t, data.errors));
    }

    return {
        showCreate,
        newContact,
        createErrors,
        createLoading,
        openCreate,
        submitCreate,
    };
}
