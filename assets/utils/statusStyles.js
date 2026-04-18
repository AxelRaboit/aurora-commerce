const POST_STATUS_CLASSES = {
    published: "bg-emerald-500/15 text-emerald-400",
    draft: "bg-amber-500/15 text-amber-400",
    trash: "bg-rose-500/15 text-rose-400",
};

/**
 * Returns Tailwind classes for a post status badge.
 * @param {string} status
 * @returns {string}
 */
export function statusBadge(status) {
    return POST_STATUS_CLASSES[status] ?? "bg-surface-2 text-secondary";
}
