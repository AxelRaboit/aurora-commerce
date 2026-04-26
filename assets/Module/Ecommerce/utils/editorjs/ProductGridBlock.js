/**
 * ProductGrid block for Editor.js — embeds Ecommerce Listings inside Editorial posts/pages.
 *
 * Manual selection only (V1): admin picks specific listings via search.
 *
 * Saved data shape:
 *   {
 *     listingIds: number[],
 *     columns: 1..4,
 *     title?: string,
 *   }
 */
export default class ProductGridBlock {
    #wrapper = null;
    #data;
    #labels;
    #searchUrl;
    #selectedCache = new Map();
    #searchAbort = null;

    static get toolbox() {
        return {
            title: "Grille de produits",
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        };
    }

    constructor({ data, config = {} }) {
        this.#data = {
            listingIds: Array.isArray(data.listingIds)
                ? data.listingIds.filter((id) => Number.isInteger(id))
                : [],
            columns: typeof data.columns === "number" ? data.columns : 3,
            title: data.title ?? "",
        };
        this.#searchUrl =
            config.searchUrl ?? "/admin/ecommerce/listings/search";
        this.#labels = {
            title: config.titleLabel ?? "Titre (optionnel)",
            columns: config.columnsLabel ?? "Colonnes",
            searchPlaceholder:
                config.searchPlaceholderLabel ?? "Rechercher un produit…",
            selected: config.selectedLabel ?? "Produits sélectionnés",
            empty: config.emptyLabel ?? "Aucun produit sélectionné",
            noResults: config.noResultsLabel ?? "Aucun résultat",
        };
    }

    render() {
        this.#wrapper = document.createElement("div");
        this.#wrapper.className = "posts-list-block";
        this.#wrapper.innerHTML = `
            <div class="posts-list-block__header">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Grille de produits</span>
            </div>

            <div class="posts-list-block__field">
                <label>${this.#labels.title}</label>
                <input type="text" data-field="title" value="${this.#escape(this.#data.title)}" placeholder="Ex: Nos best-sellers">
            </div>

            <div class="posts-list-block__field">
                <label>${this.#labels.columns}</label>
                <select data-field="columns">
                    <option value="1" ${this.#data.columns === 1 ? "selected" : ""}>1</option>
                    <option value="2" ${this.#data.columns === 2 ? "selected" : ""}>2</option>
                    <option value="3" ${this.#data.columns === 3 ? "selected" : ""}>3</option>
                    <option value="4" ${this.#data.columns === 4 ? "selected" : ""}>4</option>
                </select>
            </div>

            <div class="posts-list-block__field posts-list-block__search">
                <label>${this.#labels.searchPlaceholder.replace(/…$/, "")}</label>
                <div class="posts-list-block__search-input">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" data-search placeholder="${this.#escape(this.#labels.searchPlaceholder)}">
                </div>
                <div class="posts-list-block__results" data-results hidden></div>
            </div>

            <div class="posts-list-block__field">
                <label>${this.#labels.selected} <span class="posts-list-block__count" data-count></span></label>
                <div class="posts-list-block__selected" data-selected></div>
            </div>
        `;

        this.#wrapper.querySelectorAll("[data-field]").forEach((el) => {
            el.addEventListener("input", () => this.#syncField(el));
            el.addEventListener("change", () => this.#syncField(el));
        });

        const searchInput = this.#wrapper.querySelector("[data-search]");
        searchInput?.addEventListener("input", () =>
            this.#onSearchInput(searchInput),
        );
        searchInput?.addEventListener("focus", () =>
            this.#onSearchInput(searchInput),
        );
        document.addEventListener("click", this.#onDocClick);

        this.#hydrateSelected();

        return this.#wrapper;
    }

    #onDocClick = (event) => {
        if (!this.#wrapper) return;
        const results = this.#wrapper.querySelector("[data-results]");
        if (!results) return;
        if (!this.#wrapper.contains(event.target)) results.hidden = true;
    };

    destroy() {
        document.removeEventListener("click", this.#onDocClick);
    }

    #syncField(el) {
        const field = el.dataset.field;
        const value = el.value;
        if (field === "columns") {
            this.#data[field] = parseInt(value, 10) || 3;
        } else {
            this.#data[field] = value;
        }
    }

    async #onSearchInput(input) {
        const query = input.value.trim();
        const results = this.#wrapper.querySelector("[data-results]");
        if (!results) return;
        if ("" === query) {
            results.hidden = true;
            results.innerHTML = "";
            return;
        }

        if (this.#searchAbort) this.#searchAbort.abort();
        this.#searchAbort = new AbortController();

        try {
            const response = await fetch(
                `${this.#searchUrl}?q=${encodeURIComponent(query)}`,
                {
                    signal: this.#searchAbort.signal,
                    headers: { Accept: "application/json" },
                },
            );
            if (!response.ok) return;
            const json = await response.json();
            this.#renderSearchResults(json.results ?? [], results);
        } catch (error) {
            if (error.name !== "AbortError") results.hidden = true;
        }
    }

    #renderSearchResults(items, container) {
        if (0 === items.length) {
            container.innerHTML = `<div class="posts-list-block__result posts-list-block__result--empty">${this.#escape(this.#labels.noResults)}</div>`;
            container.hidden = false;
            return;
        }

        container.innerHTML = items
            .map(
                (item) =>
                    `<button type="button" class="posts-list-block__result" data-id="${item.id}" data-title="${this.#escape(item.title ?? "—")}"><span>${this.#escape(item.title ?? "—")}</span><small>/${this.#escape(item.slug ?? "")}</small></button>`,
            )
            .join("");
        container.hidden = false;

        container.querySelectorAll("[data-id]").forEach((btn) => {
            btn.addEventListener("click", () => {
                const id = parseInt(btn.dataset.id, 10);
                const title = btn.dataset.title;
                if (!Number.isInteger(id) || this.#data.listingIds.includes(id))
                    return;
                this.#data.listingIds.push(id);
                this.#selectedCache.set(id, { id, title });
                this.#renderSelected(
                    this.#data.listingIds.map(
                        (lid) =>
                            this.#selectedCache.get(lid) ?? {
                                id: lid,
                                title: `#${lid}`,
                            },
                    ),
                );
                container.hidden = true;
                container.innerHTML = "";
                const searchInput =
                    this.#wrapper.querySelector("[data-search]");
                if (searchInput) searchInput.value = "";
            });
        });
    }

    async #hydrateSelected() {
        if (0 === this.#data.listingIds.length) {
            this.#renderSelected([]);
            return;
        }
        const missing = this.#data.listingIds.filter(
            (id) => !this.#selectedCache.has(id),
        );
        if (missing.length > 0) {
            try {
                const response = await fetch(
                    `${this.#searchUrl}?ids=${missing.join(",")}`,
                    {
                        headers: { Accept: "application/json" },
                    },
                );
                if (response.ok) {
                    const json = await response.json();
                    (json.results ?? []).forEach((item) =>
                        this.#selectedCache.set(item.id, item),
                    );
                }
            } catch {
                // ignore
            }
        }
        this.#renderSelected(
            this.#data.listingIds.map(
                (lid) =>
                    this.#selectedCache.get(lid) ?? {
                        id: lid,
                        title: `#${lid}`,
                    },
            ),
        );
    }

    #renderSelected(items) {
        const container = this.#wrapper?.querySelector("[data-selected]");
        if (!container) return;
        const countEl = this.#wrapper.querySelector("[data-count]");
        if (countEl)
            countEl.textContent = items.length > 0 ? `(${items.length})` : "";
        if (0 === items.length) {
            container.innerHTML = `<div class="posts-list-block__empty">${this.#escape(this.#labels.empty)}</div>`;
            return;
        }
        container.innerHTML = items
            .map(
                (
                    item,
                    index,
                ) => `<div class="posts-list-block__chip" data-id="${item.id}">
                <button type="button" class="posts-list-block__chip-move" data-action="up" data-index="${index}" ${index === 0 ? "disabled" : ""} aria-label="Up">↑</button>
                <button type="button" class="posts-list-block__chip-move" data-action="down" data-index="${index}" ${index === items.length - 1 ? "disabled" : ""} aria-label="Down">↓</button>
                <span class="posts-list-block__chip-title">${this.#escape(item.title ?? `#${item.id}`)}</span>
                <button type="button" class="posts-list-block__chip-remove" data-action="remove" data-index="${index}" aria-label="Remove">×</button>
            </div>`,
            )
            .join("");

        container.querySelectorAll("[data-action]").forEach((btn) => {
            btn.addEventListener("click", () => {
                const action = btn.dataset.action;
                const index = parseInt(btn.dataset.index, 10);
                if (action === "remove") {
                    this.#data.listingIds.splice(index, 1);
                } else if (action === "up" && index > 0) {
                    [
                        this.#data.listingIds[index - 1],
                        this.#data.listingIds[index],
                    ] = [
                        this.#data.listingIds[index],
                        this.#data.listingIds[index - 1],
                    ];
                } else if (
                    action === "down" &&
                    index < this.#data.listingIds.length - 1
                ) {
                    [
                        this.#data.listingIds[index + 1],
                        this.#data.listingIds[index],
                    ] = [
                        this.#data.listingIds[index],
                        this.#data.listingIds[index + 1],
                    ];
                }
                this.#renderSelected(
                    this.#data.listingIds.map(
                        (lid) =>
                            this.#selectedCache.get(lid) ?? {
                                id: lid,
                                title: `#${lid}`,
                            },
                    ),
                );
            });
        });
    }

    #escape(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/"/g, "&quot;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }

    save() {
        return {
            listingIds: this.#data.listingIds,
            columns: Math.max(1, Math.min(4, this.#data.columns)),
            title: this.#data.title,
        };
    }
}
