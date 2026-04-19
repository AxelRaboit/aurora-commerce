import { useI18n } from "vue-i18n";
import { Locale, LOCALE_LABELS } from "@/utils/lang.js";

export const SUPPORTED_LOCALES = Object.values(Locale).map((code) => ({
    code,
    label: LOCALE_LABELS[code],
}));

export function useLocale(endpoint = "/admin/profile/locale") {
    const { locale } = useI18n();

    async function setLocale(code) {
        try {
            await fetch(endpoint, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ locale: code }),
            });
        } catch {
            console.warn("[useLocale] Failed to persist locale on server.");
        }
        locale.value = code;
        localStorage.setItem("velox-locale", code);
    }

    return { locale, setLocale, SUPPORTED_LOCALES };
}
