import { createI18n } from "vue-i18n";

/**
 * Build a minimal vue-i18n instance for component tests.
 * Messages are merged on top of a small default that covers the translation
 * keys shared by many admin components (common, locales, posts labels).
 */
export function createTestI18n(messages = {}, locale = "fr") {
    return createI18n({
        legacy: false,
        locale,
        fallbackLocale: locale,
        messages: {
            [locale]: deepMerge(baseMessages, messages),
        },
    });
}

const baseMessages = {
    shared: {
        common: {
            cancel: "Annuler",
            save: "Enregistrer",
            confirm: "Confirmer",
            error: "Erreur",
        },
        locales: {
            fr: "Français",
            en: "English",
            es: "Español",
            de: "Deutsch",
        },
    },
};

function deepMerge(target, source) {
    const out = { ...target };
    for (const [key, value] of Object.entries(source)) {
        if (value && typeof value === "object" && !Array.isArray(value)) {
            out[key] = deepMerge(out[key] ?? {}, value);
        } else {
            out[key] = value;
        }
    }
    return out;
}
