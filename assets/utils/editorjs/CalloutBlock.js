const DEFAULT_TYPES = [
    { value: "info", label: "Info" },
    { value: "success", label: "Success" },
    { value: "warning", label: "Warning" },
    { value: "danger", label: "Danger" },
    { value: "tip", label: "Tip" },
    { value: "note", label: "Note" },
    { value: "question", label: "Question" },
    { value: "important", label: "Important" },
    { value: "update", label: "Update" },
    { value: "rose", label: "Rose" },
    { value: "indigo", label: "Indigo" },
    { value: "lime", label: "Lime" },
    { value: "amber", label: "Amber" },
    { value: "fuchsia", label: "Fuchsia" },
];

export default class CalloutBlock {
    #wrapper = null;
    #titleEl = null;
    #messageEl = null;
    #data;
    #types;
    #titlePlaceholder;
    #messagePlaceholder;

    static get toolbox() {
        return {
            title: "Callout",
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        };
    }

    static get enableLineBreaks() {
        return true;
    }

    constructor({ data, config = {} }) {
        this.#data = {
            type: data.type ?? "info",
            title: data.title ?? "",
            message: data.message ?? "",
        };
        this.#types = config.types ?? DEFAULT_TYPES;
        this.#titlePlaceholder = config.titlePlaceholder ?? "Title…";
        this.#messagePlaceholder = config.messagePlaceholder ?? "Message…";
    }

    render() {
        this.#wrapper = document.createElement("div");
        this.#rebuild();
        return this.#wrapper;
    }

    #rebuild() {
        this.#wrapper.innerHTML = "";
        this.#wrapper.className = `callout-block callout-block--${this.#data.type}`;
        this.#wrapper.appendChild(this.#createTabs());
        this.#titleEl = this.#createEditable(
            "callout-block__title",
            this.#titlePlaceholder,
            "title",
        );
        this.#messageEl = this.#createEditable(
            "callout-block__message",
            this.#messagePlaceholder,
            "message",
        );
        this.#wrapper.appendChild(this.#titleEl);
        this.#wrapper.appendChild(this.#messageEl);
    }

    #createTabs() {
        const tabs = document.createElement("div");
        tabs.className = "callout-block__tabs";
        this.#types.forEach(({ value, label }) => {
            tabs.appendChild(this.#createTab(value, label));
        });
        return tabs;
    }

    #createTab(value, label) {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = `callout-block__tab callout-block__tab--${value}${this.#data.type === value ? " callout-block__tab--active" : ""}`;
        btn.title = label;
        btn.addEventListener("click", () => {
            this.#data.type = value;
            this.#rebuild();
        });
        return btn;
    }

    #createEditable(className, placeholder, dataKey) {
        const el = document.createElement("div");
        el.contentEditable = "true";
        el.className = className;
        el.dataset.placeholder = placeholder;
        el.innerHTML = this.#data[dataKey];
        el.addEventListener("input", () => {
            this.#data[dataKey] = el.innerHTML;
        });
        return el;
    }

    save() {
        return {
            type: this.#data.type,
            title: this.#titleEl?.innerHTML ?? this.#data.title,
            message: this.#messageEl?.innerHTML ?? this.#data.message,
        };
    }

    validate(data) {
        return !!(data.title || data.message);
    }
}
