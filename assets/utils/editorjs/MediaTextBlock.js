export default class MediaTextBlock {
    #wrapper = null;
    #data;
    #config;

    static get toolbox() {
        return {
            title: "Image + Text",
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="18" rx="1"/><rect x="13" y="3" width="8" height="18" rx="1"/></svg>',
        };
    }

    static get enableLineBreaks() {
        return true;
    }

    constructor({ data, config = {} }) {
        this.#data = {
            url: data.url ?? "",
            caption: data.caption ?? "",
            text: data.text ?? "",
            flip: data.flip ?? false,
        };
        this.#config = {
            flipLeft: config.flipLeft ?? "⇄ Image left",
            flipRight: config.flipRight ?? "⇄ Image right",
            captionPlaceholder: config.captionPlaceholder ?? "Caption…",
            textPlaceholder: config.textPlaceholder ?? "Your text…",
            urlPlaceholder: config.urlPlaceholder ?? "Image URL…",
            changeUrl: config.changeUrl ?? "Change URL",
            confirm: config.confirm ?? "Confirm",
        };
    }

    render() {
        this.#wrapper = document.createElement("div");
        this.#wrapper.classList.add("media-text-block");
        this.#rebuild();
        return this.#wrapper;
    }

    #rebuild() {
        this.#wrapper.innerHTML = "";
        this.#wrapper.dataset.flip = this.#data.flip ? "1" : "0";
        this.#wrapper.appendChild(this.#createToolbar());
        this.#wrapper.appendChild(this.#createRow());
    }

    #createToolbar() {
        const toolbar = document.createElement("div");
        toolbar.className = "mt-block__toolbar";
        const flipBtn = this.#createButton(
            this.#data.flip ? this.#config.flipRight : this.#config.flipLeft,
            () => {
                this.#data.flip = !this.#data.flip;
                this.#rebuild();
            },
        );
        toolbar.appendChild(flipBtn);
        return toolbar;
    }

    #createRow() {
        const row = document.createElement("div");
        row.className = "mt-block__row";
        const imgCol = this.#createImageCol();
        const textCol = this.#createTextCol();
        row.appendChild(this.#data.flip ? textCol : imgCol);
        row.appendChild(this.#data.flip ? imgCol : textCol);
        return row;
    }

    #createImageCol() {
        const col = document.createElement("div");
        col.className = "mt-block__img-col";

        if (this.#data.url) {
            const img = document.createElement("img");
            img.src = this.#data.url;
            img.alt = this.#data.caption;
            img.className = "mt-block__img";

            const cap = document.createElement("p");
            cap.className = "mt-block__caption";
            cap.contentEditable = "true";
            cap.dataset.placeholder = this.#config.captionPlaceholder;
            cap.innerHTML = this.#data.caption;
            cap.addEventListener("input", () => {
                this.#data.caption = cap.innerHTML;
            });

            col.appendChild(img);
            col.appendChild(cap);
            col.appendChild(
                this.#createButton(this.#config.changeUrl, () =>
                    this.#promptUrl(col),
                ),
            );
        } else {
            col.appendChild(
                this.#createUrlForm((url) => {
                    this.#data.url = url;
                    this.#rebuild();
                }),
            );
        }

        return col;
    }

    #createTextCol() {
        const col = document.createElement("div");
        col.className = "mt-block__text-col";

        const editable = document.createElement("div");
        editable.contentEditable = "true";
        editable.className = "mt-block__text";
        editable.dataset.placeholder = this.#config.textPlaceholder;
        editable.innerHTML = this.#data.text;
        editable.addEventListener("input", () => {
            this.#data.text = editable.innerHTML;
        });

        col.appendChild(editable);
        return col;
    }

    #promptUrl(col) {
        col.innerHTML = "";
        col.appendChild(
            this.#createUrlForm((url) => {
                this.#data.url = url;
                this.#rebuild();
            }),
        );
    }

    #createUrlForm(onConfirm) {
        const fragment = document.createDocumentFragment();

        const input = document.createElement("input");
        input.type = "url";
        input.placeholder = this.#config.urlPlaceholder;
        input.className = "mt-block__url-input";

        const confirm = this.#createButton(this.#config.confirm, () => {
            const url = input.value.trim();
            if (url) onConfirm(url);
        });

        fragment.appendChild(input);
        fragment.appendChild(confirm);
        return fragment;
    }

    #createButton(text, onClick) {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "mt-block__btn";
        btn.textContent = text;
        btn.addEventListener("click", onClick);
        return btn;
    }

    save() {
        return { ...this.#data };
    }

    validate(data) {
        return !!(data.url || data.text);
    }
}
