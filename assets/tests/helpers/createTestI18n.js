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
            cancel: "Cancel",
            save: "Save",
            confirm: "Confirm",
            error: "Error",
            deleted: "Deleted.",
            close: "Close",
            loadMore: "Load more",
            no_options: "No options",
            no_result: "No results",
            pagination: "Page {page} of {totalPages}",
            select_placeholder: "Select…",
            add_tag_hint: "Add a tag",
        },
        dropZone: {
            cta: "Click or drag a file here",
            drop: "Drop to upload",
            uploading: "Uploading…",
        },
        media: {
            change: "Change",
            choose: "Choose",
            remove: "Remove",
        },
        pagination: {
            previous: "Previous",
            next: "Next",
        },
        locales: {
            fr: "Français",
            en: "English",
            es: "Español",
            de: "Deutsch",
        },
    },
    backend: {
        settings: {
            saved: "Réglages sauvegardés.",
            confirmPasswordInvalid: "Mot de passe incorrect.",
            cascadeLocked: "Activez d'abord « {parent} ».",
        },
        projects: {
            errors: {
                comment_required: "Le commentaire est requis.",
                item_label_required: "Le libellé est requis.",
                time_minutes_invalid: "Durée invalide.",
            },
        },
        ecommerce: {
            orders: {
                refund: {
                    success: "Remboursement effectué.",
                },
            },
            errors: {
                refund_failed: "Remboursement échoué.",
            },
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
