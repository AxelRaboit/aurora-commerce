import { HttpMethod } from "@/shared/utils/httpMethod.js";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

export function useProfileMood(moodPath, initialMessage, maxLength) {
    const { t } = useI18n();

    const moodMessage = ref(initialMessage ?? "");
    const moodLoading = ref(false);
    const moodError = ref("");

    async function saveMood() {
        const trimmed = moodMessage.value.trim();
        if (trimmed.length > maxLength) {
            moodError.value = t("admin.profile.mood.errors.too_long");
            return;
        }

        moodLoading.value = true;
        moodError.value = "";
        try {
            const response = await fetch(moodPath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ moodMessage: trimmed }),
            });
            const data = await response.json();
            if (data.success) {
                moodMessage.value = data.moodMessage ?? "";
                toast.success(t("admin.profile.mood.saved"));
            } else {
                moodError.value = data.errors?.moodMessage ?? t("shared.common.error");
            }
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            moodLoading.value = false;
        }
    }

    return { moodMessage, moodLoading, moodError, saveMood };
}
