export function seoCounterClass(length, max) {
    if (length === 0) return "text-muted";
    if (length <= max * 0.85) return "text-green-500";
    if (length <= max) return "text-amber-500";
    return "text-red-500";
}
