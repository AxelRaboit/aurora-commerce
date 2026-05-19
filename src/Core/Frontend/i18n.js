import { createI18n } from "vue-i18n";
import { deepMerge } from "@/shared/utils/data/deepMerge.js";

// Generated from translations/messages.{locale}.yaml via `php bin/console app:translations:dump-js`.
// Single source of truth for all Vue + Twig translations.
import frYaml from "@/locales/generated/fr.json";
import enYaml from "@/locales/generated/en.json";

// Optional client-specific locale sources (e.g. custom module permission names).
// Resolves via the @client alias; returns {} when AURORA_CLIENT_DIR is unset.
// Keys use resolved paths, so we match by filename suffix instead of the alias literal.
const clientLocales = import.meta.glob("@client/locales/*.js", { eager: true });
const clientFr =
    Object.entries(clientLocales).find(([k]) => k.endsWith("/fr.js"))?.[1]
        ?.default ?? {};
const clientEn =
    Object.entries(clientLocales).find(([k]) => k.endsWith("/en.js"))?.[1]
        ?.default ?? {};

// Client wins last so custom modules can override or extend any key.
const fr = deepMerge(frYaml, clientFr);
const en = deepMerge(enYaml, clientEn);

export function createAppI18n(locale = "fr") {
    return createI18n({
        legacy: false,
        locale,
        fallbackLocale: "fr",
        messages: { fr, en },
    });
}
