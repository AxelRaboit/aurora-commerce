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
