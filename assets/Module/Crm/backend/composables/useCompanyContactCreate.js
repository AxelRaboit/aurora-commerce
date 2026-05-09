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
    const {
        errors: contactErrors,
        validate,
        clearErrors,
        setErrors,
    } = useForm();
    const { loading: contactLoading, request } = useApiRequest();

    function openCreateContact() {
        newContact.value = emptyContactForm();
        clearErrors();
        showCreateContact.value = true;
    }

    async function submitContact() {
        if (
            !validate({
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
            })
        )
            return;
        const data = await request(createContactPath, {
            ...newContact.value,
            companyId,
        });
        if (!data) return;
        if (data.success) {
            showCreateContact.value = false;
            toast.success(t("backend.crm.contacts.created"));
            if (data.contact)
                contacts.value = [data.contact, ...contacts.value];
        } else {
            setErrors(translateServerErrors(t, data.errors));
        }
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
