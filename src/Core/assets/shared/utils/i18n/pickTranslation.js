/**
 * Translation fallback helpers for entities whose API payload exposes
 * `translations: { [localeCode]: { … } }`.
 *
 * Fallback chain: requested locale → fallbackLocale → first available → null.
 */

export function pickTranslation(entity, locale, fallbackLocale = "en") {
    const translations = entity?.translations ?? {};
    return (
        translations[locale] ??
        translations[fallbackLocale] ??
        Object.values(translations)[0] ??
        null
    );
}

export function translatedField(entity, field, locale, fallback = "") {
    const translation = pickTranslation(entity, locale);
    return translation?.[field] ?? fallback;
}
