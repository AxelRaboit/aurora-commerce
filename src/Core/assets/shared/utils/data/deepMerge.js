/**
 * Deep-merges plain objects. The second argument's keys win on conflict.
 * Arrays and primitives are replaced (not merged).
 */
export function deepMerge(target, source) {
    if (!isPlainObject(target) || !isPlainObject(source))
        return source ?? target;
    const out = { ...target };
    for (const [key, value] of Object.entries(source)) {
        out[key] =
            isPlainObject(value) && isPlainObject(out[key])
                ? deepMerge(out[key], value)
                : value;
    }
    return out;
}

function isPlainObject(value) {
    return value !== null && typeof value === "object" && !Array.isArray(value);
}
