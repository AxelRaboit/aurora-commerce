import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import {
    required,
    email as emailValidator,
} from "@/shared/utils/validation/validators.js";

function emptyContactForm() {
    return { firstName: "", lastName: "", email: "", phone: "", notes: "" };
}

export function useCompanyContactCreate(
    createContactPath,
    companyId,
    contacts,
) {
    const { t } = useI18n();

    const showCreateContact = ref(false);
    const newContact = ref(emptyContactForm());

    const { errors: contactErrors, loading: contactLoading, submit: submitContact, clearErrors } = useFormAction({
        rules: () => ({
            firstName: () =>
                required(
                    t("backend.crm.contacts.errors.first_name_required"),
                )(newContact.value.firstName),
            lastName: () =>
                required(
                    t("backend.crm.contacts.errors.last_name_required"),
                )(newContact.value.lastName),
            email: () =>
                newContact.value.email
                    ? emailValidator(
                          t("backend.crm.contacts.errors.email_invalid"),
                      )(newContact.value.email)
                    : null,
        }),
        url: () => createContactPath,
        body: () => ({ ...newContact.value, companyId }),
        onSuccess: (data) => {
            showCreateContact.value = false;
            toast.success(t("backend.crm.contacts.created"));
            if (data.contact)
                contacts.value = [data.contact, ...contacts.value];
        },
    });

    function openCreateContact() {
        newContact.value = emptyContactForm();
        clearErrors();
        showCreateContact.value = true;
    }

    return {
        showCreateContact,
        newContact,
        contactErrors,
        contactLoading,
        openCreateContact,
        submitContact,
    };
}
