import { createI18n } from "vue-i18n";
import { deepMerge } from "@/shared/utils/data/deepMerge.js";

// Manual JS sources (Vue-only keys: admin form labels, validation messages, etc.).
import frSource from "@/locales/source/fr.js";
import enSource from "@/locales/source/en.js";

// Generated from translations/messages.{locale}.yaml via `php bin/console app:translations:dump-js`.
// Shared keys (used in both Twig and Vue) live there — single source of truth on the YAML side.
import frYaml from "@/locales/generated/fr.json";
import enYaml from "@/locales/generated/en.json";

// YAML wins on conflict so updates to messages.yaml propagate without touching JS sources.
const fr = deepMerge(frSource, frYaml);
const en = deepMerge(enSource, enYaml);

export function createAppI18n(locale = "fr") {
    return createI18n({
        legacy: false,
        locale,
        fallbackLocale: "fr",
        messages: { fr, en },
    });
}
