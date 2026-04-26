/**
 * Safely parses a JSON string, returning a fallback value on failure.
 * Idempotent: if the input is already an object, it is returned as-is.
 * @template T
 * @param {string|T|null|undefined} value
 * @param {T} fallback
 * @returns {T}
 */
export function parseJson(value, fallback) {
    if (value === null || value === undefined) return fallback;
    if (typeof value === "object") return value;
    try {
        return JSON.parse(value) ?? fallback;
    } catch {
        return fallback;
    }
}
