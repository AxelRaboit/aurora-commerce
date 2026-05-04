import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

export function useGalleryFinalize({
    finalizePath,
    gallery,
    finalized,
    identityKnown,
    visitorName,
    visitorEmail,
}) {
    const { t } = useI18n();

    const showFinalizeModal = ref(false);
    const finalizeName = ref("");
    const finalizeEmail = ref("");
    const finalizing = ref(false);
    const finalizeNameError = ref("");
    const finalizeEmailError = ref("");

    function openFinalize() {
        if (gallery.picksRequireIdentity || identityKnown.value) {
            finalizeName.value = visitorName.value;
            finalizeEmail.value = visitorEmail.value;
        }
        showFinalizeModal.value = true;
    }

    async function submitFinalize() {
        finalizeNameError.value = "";
        finalizeEmailError.value = "";
        finalizing.value = true;
        try {
            const response = await fetch(finalizePath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    name: finalizeName.value,
                    email: finalizeEmail.value,
                }),
            });
            const data = await response.json();
            if (!data?.success) {
                const errors = translateServerErrors(t, data?.errors);
                finalizeNameError.value = errors.name ?? "";
                finalizeEmailError.value = errors.email ?? "";
                if (!errors.name && !errors.email)
                    toast.error(t("shared.common.error"));
                return;
            }
            finalized.value = true;
            showFinalizeModal.value = false;
            toast.success(t("photo.frontend.finalizedToast"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            finalizing.value = false;
        }
    }

    return {
        showFinalizeModal,
        finalizeName,
        finalizeEmail,
        finalizing,
        finalizeNameError,
        finalizeEmailError,
        openFinalize,
        submitFinalize,
    };
}
