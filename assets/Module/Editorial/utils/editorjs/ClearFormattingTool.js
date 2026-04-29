const ICON = `<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4 9 11"/><path d="M16 4h4v4"/><path d="m6 16 6-6"/><path d="M3 21h7"/><path d="M21 21 3 3"/></svg>`;

const INLINE_TAGS = new Set([
    "SPAN", "U", "S", "STRIKE", "MARK", "B", "STRONG", "I", "EM", "FONT", "CODE", "SUB", "SUP", "SMALL",
]);

export class ClearFormattingTool {
    static get isInline() { return true; }
    static get title() { return "Clear formatting"; }

    constructor({ api }) {
        this.api = api;
    }

    render() {
        return {
            icon: ICON,
            name: "clear-formatting",
            title: "Clear formatting",
            onActivate: () => this._clear(),
        };
    }

    _clear() {
        const sel = window.getSelection();
        if (!sel || !sel.rangeCount || sel.isCollapsed) return;

        // 1. Browser-level cleanup (handles bold, italic, color, font, etc.)
        document.execCommand("removeFormat");

        // 2. Manual cleanup for tags removeFormat may not handle (custom spans, u, s, mark, ...)
        const sel2 = window.getSelection();
        if (!sel2 || !sel2.rangeCount) return;
        const range = sel2.getRangeAt(0);

        const root = range.commonAncestorContainer.nodeType === Node.TEXT_NODE
            ? range.commonAncestorContainer.parentNode
            : range.commonAncestorContainer;
        if (!root || !root.querySelectorAll) return;

        const candidates = Array.from(root.querySelectorAll("*"))
            .filter((el) => INLINE_TAGS.has(el.tagName) && range.intersectsNode(el));

        candidates.forEach((el) => {
            const parent = el.parentNode;
            if (!parent) return;
            while (el.firstChild) parent.insertBefore(el.firstChild, el);
            parent.removeChild(el);
        });

        if (root.normalize) root.normalize();
    }
}
