const ICON = `<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4H9a3 3 0 0 0-2.83 4"/><path d="M14 12a4 4 0 0 1 0 8H6"/><line x1="4" x2="20" y1="12" y2="12"/></svg>`;

export class StrikethroughTool {
    static get isInline() { return true; }
    static get title() { return "Strikethrough"; }
    static get sanitize() { return { s: {}, strike: {} }; }
    static get shortcut() { return "CMD+SHIFT+X"; }

    constructor({ api }) {
        this.api = api;
    }

    render() {
        return {
            icon: ICON,
            name: "strikethrough",
            title: "Strikethrough",
            toggle: true,
            isActive: () => this._isActive(),
            onActivate: () => document.execCommand("strikeThrough"),
        };
    }

    _isActive() {
        return !!(
            this.api.selection.findParentTag("S") ||
            this.api.selection.findParentTag("STRIKE")
        );
    }

    checkState() {
        return this._isActive();
    }
}
