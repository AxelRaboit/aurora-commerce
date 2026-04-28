import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { ref } from "vue";

export function useProfileLocale(localePath, initialLocale) {
    const selectedLocale = ref(initialLocale);
    const localeLoading = ref(false);

    async function changeLocale() {
        if (selectedLocale.value === initialLocale) return;
        localeLoading.value = true;
        try {
            const response = await fetch(localePath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ locale: selectedLocale.value }),
            });
            if (!response.ok) {
                selectedLocale.value = initialLocale;
                return;
            }
            window.location.reload();
        } catch {
            selectedLocale.value = initialLocale;
        } finally {
            localeLoading.value = false;
        }
    }

    return { selectedLocale, localeLoading, changeLocale };
}
