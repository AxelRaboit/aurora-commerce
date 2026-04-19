/**
 * Safely parses a JSON string, returning a fallback value on failure.
 * @template T
 * @param {string} value
 * @param {T} fallback
 * @returns {T}
 */
export function parseJson(value, fallback) {
    try {
        return JSON.parse(value) ?? fallback;
    } catch {
        return fallback;
    }
}
