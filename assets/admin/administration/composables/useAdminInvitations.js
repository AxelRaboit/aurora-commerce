import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useForm } from "@/composables/useForm.js";
import { submitForm } from "@/utils/formSubmit.js";
import { required, email, compose } from "@/utils/validators.js";

export function useAdminInvitations(invitationSendPath, csrfToken) {
    const { t: translate } = useI18n();
    const { errors: invitationErrors, validate: validateInvitation } = useForm();

    const invitationEmail = ref("");
    const invitationMessage = ref("");
    const invitationCredentialEmail = ref("");
    const invitationCredentialPassword = ref("");
    const invitationSending = ref(false);

    function submitInvitation() {
        const isValid = validateInvitation({
            email: () => compose(
                required(translate("profile.errors.email_invalid")),
                email(translate("profile.errors.email_invalid")),
            )(invitationEmail.value),
        });

        if (!isValid || invitationSending.value) return;
        invitationSending.value = true;
        submitForm(invitationSendPath, csrfToken, {
            email: invitationEmail.value,
            message: invitationMessage.value,
            credential_email: invitationCredentialEmail.value,
            credential_password: invitationCredentialPassword.value,
        });
    }

    return {
        invitationEmail,
        invitationMessage,
        invitationCredentialEmail,
        invitationCredentialPassword,
        invitationSending,
        invitationErrors,
        submitInvitation,
    };
}
