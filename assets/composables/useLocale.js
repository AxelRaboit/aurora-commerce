import { useI18n } from "vue-i18n";

export const SUPPORTED_LOCALES = [
    { code: "fr", label: "Français" },
    { code: "en", label: "English" },
    { code: "es", label: "Español" },
    { code: "de", label: "Deutsch" },
];

export function useLocale(endpoint = "/admin/profile/locale") {
    const { locale } = useI18n();

    async function setLocale(code) {
        try {
            await fetch(endpoint, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ locale: code }),
            });
        } catch (e) {
            console.warn("[useLocale] Failed to persist locale on server:", e);
        }
        locale.value = code;
        localStorage.setItem("velox-locale", code);
    }

    return { locale, setLocale, SUPPORTED_LOCALES };
}
