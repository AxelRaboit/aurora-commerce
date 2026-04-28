const HIGHLIGHT_MARK =
    '<mark class="bg-accent-400/30 text-primary rounded px-0.5">$1</mark>';

export function highlightMatch(text, query) {
    if (!text || !query) return text ?? "";
    const tokens = query
        .trim()
        .split(/\s+/)
        .filter((token) => token.length > 1)
        .map((token) => token.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"));
    if (!tokens.length) return text;
    const regex = new RegExp(`(${tokens.join("|")})`, "ig");
    return text.replace(regex, HIGHLIGHT_MARK);
}
