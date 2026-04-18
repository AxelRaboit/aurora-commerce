import { createI18n } from "vue-i18n";
import fr from "./locales/fr.js";
import en from "./locales/en.js";
import es from "./locales/es.js";
import de from "./locales/de.js";

export function createAppI18n(locale = "fr") {
    return createI18n({
        legacy: false,
        locale,
        fallbackLocale: "fr",
        messages: { fr, en, es, de },
    });
}
