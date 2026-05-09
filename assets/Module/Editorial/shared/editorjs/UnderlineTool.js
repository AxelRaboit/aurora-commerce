const TAG = "U";

const ICON = `<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4v6a6 6 0 0 0 12 0V4"/><line x1="4" x2="20" y1="20" y2="20"/></svg>`;

export class UnderlineTool {
    static get isInline() {
        return true;
    }
    static get title() {
        return "Underline";
    }
    static get sanitize() {
        return { u: {} };
    }
    static get shortcut() {
        return "CMD+U";
    }

    constructor({ api }) {
        this.api = api;
    }

    render() {
        return {
            icon: ICON,
            name: "underline",
            title: "Underline",
            toggle: true,
            isActive: () => !!this.api.selection.findParentTag(TAG),
            onActivate: () => document.execCommand("underline"),
        };
    }

    checkState() {
        return !!this.api.selection.findParentTag(TAG);
    }
}
