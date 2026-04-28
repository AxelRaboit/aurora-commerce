/**
 * Fills `__name__` placeholders in a URL template with the provided values,
 * URI-encoding each one so callers don't have to think about special chars.
 *
 * Examples:
 *   buildPath("/admin/users/__id__/edit", { id: 42 })
 *     → "/admin/users/42/edit"
 *
 *   buildPath("/admin/posts/__id__/fields/__fieldId__", { id: 1, fieldId: 7 })
 *     → "/admin/posts/1/fields/7"
 *
 *   buildPath("/admin/parameters/__key__", { key: "site/name" })
 *     → "/admin/parameters/site%2Fname"
 *
 * @param {string} template
 * @param {Record<string, string|number>} params
 * @returns {string}
 */
export function buildPath(template, params) {
    let result = template;
    for (const [name, value] of Object.entries(params ?? {})) {
        result = result.replaceAll(
            `__${name}__`,
            encodeURIComponent(String(value)),
        );
    }
    return result;
}
