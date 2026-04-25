/**
 * Truncates a string to the given length, adding "…" if cut.
 *
 * @param {string|null} text
 * @param {number} length
 * @returns {string}
 */
export function truncate(text, length) {
    if (!text) return "";
    return text.length > length ? text.slice(0, length) + "…" : text;
}
