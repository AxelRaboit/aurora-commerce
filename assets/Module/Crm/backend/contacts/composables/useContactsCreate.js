import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import {
    required,
    email as emailValidator,
} from "@/shared/utils/validation/validators.js";

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
        loading: createLoading,
        submit: submitCreate,
        clearErrors,
    } = useFormAction({
        rules: () => ({
            firstName: () =>
                required(t("backend.crm.contacts.errors.first_name_required"))(
                    newContact.value.firstName,
                ),
            lastName: () =>
                required(t("backend.crm.contacts.errors.last_name_required"))(
                    newContact.value.lastName,
                ),
            email: () =>
                newContact.value.email
                    ? emailValidator(
                          t("backend.crm.contacts.errors.email_invalid"),
                      )(newContact.value.email)
                    : null,
        }),
        url: () => createPath,
        body: () => newContact.value,
        onSuccess: () => {
            showCreate.value = false;
            toast.success(t("backend.crm.contacts.created"));
            reset();
        },
    });

    function openCreate() {
        newContact.value = emptyContactForm();
        clearErrors();
        showCreate.value = true;
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
