const LIST_URL = "/admin/media/list";

const i18n = {
    title: "Choose a media",
    search: "Search by name or alt…",
    allFolders: "All folders",
    empty: "No media found",
    loading: "Loading…",
    cancel: "Cancel",
    select: "Select",
};

export function configureMediaPickerLabels(labels) {
    Object.assign(i18n, labels);
}

export function openMediaPicker({ imagesOnly = true } = {}) {
    return new Promise((resolve) => {
        const overlay = document.createElement("div");
        overlay.className = "media-picker-overlay";

        const modal = document.createElement("div");
        modal.className = "media-picker-modal";

        modal.innerHTML = `
            <header class="media-picker-header">
                <h2>${i18n.title}</h2>
                <button type="button" class="media-picker-close" aria-label="Close">×</button>
            </header>
            <div class="media-picker-toolbar">
                <input type="search" class="media-picker-search" placeholder="${i18n.search}">
                <select class="media-picker-folder">
                    <option value="">${i18n.allFolders}</option>
                </select>
            </div>
            <div class="media-picker-grid" role="listbox"></div>
            <footer class="media-picker-footer">
                <button type="button" class="media-picker-cancel">${i18n.cancel}</button>
                <button type="button" class="media-picker-confirm" disabled>${i18n.select}</button>
            </footer>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        const searchInput = modal.querySelector(".media-picker-search");
        const folderSelect = modal.querySelector(".media-picker-folder");
        const grid = modal.querySelector(".media-picker-grid");
        const confirmBtn = modal.querySelector(".media-picker-confirm");
        const cancelBtn = modal.querySelector(".media-picker-cancel");
        const closeBtn = modal.querySelector(".media-picker-close");

        let selected = null;
        let searchTimer = null;

        function close(value) {
            document.body.removeChild(overlay);
            document.removeEventListener("keydown", onKey);
            resolve(value);
        }

        function onKey(event) {
            if (event.key === "Escape") close(null);
        }
        document.addEventListener("keydown", onKey);

        overlay.addEventListener("click", (event) => {
            if (event.target === overlay) close(null);
        });
        closeBtn.addEventListener("click", () => close(null));
        cancelBtn.addEventListener("click", () => close(null));
        confirmBtn.addEventListener("click", () => {
            if (selected) close(selected);
        });

        async function load() {
            grid.innerHTML = `<p class="media-picker-status">${i18n.loading}</p>`;
            try {
                const url = new URL(LIST_URL, window.location.origin);
                if (searchInput.value.trim())
                    url.searchParams.set("search", searchInput.value.trim());
                if (folderSelect.value)
                    url.searchParams.set("folderId", folderSelect.value);
                const response = await fetch(url, {
                    headers: { Accept: "application/json" },
                });
                if (!response.ok) throw new Error();
                const data = await response.json();

                if (
                    folderSelect.options.length <= 1 &&
                    Array.isArray(data.folders)
                ) {
                    for (const folder of data.folders) {
                        const opt = document.createElement("option");
                        opt.value = String(folder.id);
                        opt.textContent = folder.name ?? `#${folder.id}`;
                        folderSelect.appendChild(opt);
                    }
                }

                const items = (data.items ?? []).filter(
                    (item) => !imagesOnly || item.isImage,
                );
                renderItems(items);
            } catch {
                grid.innerHTML = `<p class="media-picker-status">${i18n.empty}</p>`;
            }
        }

        function renderItems(items) {
            grid.innerHTML = "";
            if (!items.length) {
                grid.innerHTML = `<p class="media-picker-status">${i18n.empty}</p>`;
                return;
            }
            for (const item of items) {
                const cell = document.createElement("button");
                cell.type = "button";
                cell.className = "media-picker-cell";
                cell.dataset.id = String(item.id);
                cell.innerHTML = `
                    <img src="${item.thumbnailUrl ?? item.url}" alt="${item.alt ?? ""}" loading="lazy">
                    <span class="media-picker-cell__name" title="${item.originalName ?? ""}">${item.originalName ?? ""}</span>
                `;
                cell.addEventListener("click", () => {
                    grid.querySelectorAll(
                        ".media-picker-cell.is-selected",
                    ).forEach((node) => node.classList.remove("is-selected"));
                    cell.classList.add("is-selected");
                    selected = item;
                    confirmBtn.disabled = false;
                });
                cell.addEventListener("dblclick", () => close(item));
                grid.appendChild(cell);
            }
        }

        searchInput.addEventListener("input", () => {
            if (searchTimer) clearTimeout(searchTimer);
            searchTimer = setTimeout(load, 250);
        });
        folderSelect.addEventListener("change", load);

        load();
    });
}
