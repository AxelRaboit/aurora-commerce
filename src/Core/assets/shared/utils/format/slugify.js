/**
 * Converts a string to a URL-friendly slug.
 * @param {string} text
 * @returns {string}
 */
export function slugify(text) {
    return (text ?? "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");
}

/**
 * Returns the slug derived from `source` when `currentSlug` is empty, otherwise
 * keeps `currentSlug` untouched. Used to lazily seed slug fields without
 * overriding what the user has already typed.
 * @param {string} currentSlug
 * @param {string} source
 * @returns {string}
 */
export function slugifyIfEmpty(currentSlug, source) {
    return currentSlug ? currentSlug : slugify(source);
}
