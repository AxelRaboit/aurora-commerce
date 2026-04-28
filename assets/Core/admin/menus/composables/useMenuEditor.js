import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

async function jsonRequest(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            "Content-Type": "application/json",
            ...(options.headers ?? {}),
        },
    });
    return response.json();
}

function replacePath(template, id) {
    return template.replace("__id__", String(id));
}

function flattenItems(items, list = []) {
    for (const item of items) {
        list.push({
            id: item.id,
            parentId: item.parentId,
            position: item.position,
        });
        if (item.children?.length) flattenItems(item.children, list);
    }
    return list;
}

export function useMenuEditor(paths, initialMenus) {
    const { t } = useI18n();

    const menus = ref([...initialMenus]);
    const selectedMenu = ref(null);
    const loadingMenu = ref(false);

    async function selectMenu(menu) {
        if (!menu) {
            selectedMenu.value = null;
            return;
        }
        loadingMenu.value = true;
        try {
            const data = await jsonRequest(replacePath(paths.show, menu.id));
            if (data.ok) {
                selectedMenu.value = data.menu;
            } else {
                toast.error(t("shared.common.error"));
            }
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loadingMenu.value = false;
        }
    }

    async function refreshMenu() {
        if (!selectedMenu.value) return;
        await selectMenu(selectedMenu.value);
    }

    async function refreshList() {
        const data = await jsonRequest(paths.list);
        if (data.ok) menus.value = data.menus;
    }

    async function updateMenu(menu, payload) {
        const data = await jsonRequest(replacePath(paths.update, menu.id), {
            method: HttpMethod.Post,
            body: JSON.stringify(payload),
        });
        if (data.ok) {
            toast.success(t("shared.common.saved"));
            await refreshList();
            if (data.menu) await selectMenu(data.menu);
            return true;
        }
        toast.error(t(data.error ?? "common.error"));
        return false;
    }

    async function deleteMenu(menu) {
        try {
            const data = await jsonRequest(replacePath(paths.delete, menu.id), {
                method: HttpMethod.Post,
            });
            if (data.ok) {
                toast.success(t("shared.common.deleted"));
                if (selectedMenu.value?.id === menu.id)
                    selectedMenu.value = null;
                await refreshList();
                return true;
            }
            toast.error(t("shared.common.error"));
        } catch {
            toast.error(t("shared.common.error"));
        }
        return false;
    }

    async function reorderItems() {
        if (!selectedMenu.value) return;

        const reassign = (items, parentId) => {
            items.forEach((item, index) => {
                item.parentId = parentId;
                item.position = index;
                if (item.children?.length) reassign(item.children, item.id);
            });
        };
        reassign(selectedMenu.value.items, null);

        const payload = flattenItems(selectedMenu.value.items);
        try {
            const data = await jsonRequest(
                replacePath(paths.itemReorder, selectedMenu.value.id),
                {
                    method: HttpMethod.Post,
                    body: JSON.stringify({ items: payload }),
                },
            );
            if (data.ok) {
                selectedMenu.value = data.menu;
            } else {
                toast.error(t("shared.common.error"));
                await refreshMenu();
            }
        } catch {
            toast.error(t("shared.common.error"));
            await refreshMenu();
        }
    }

    async function saveItem(editingItem, payload) {
        if (!selectedMenu.value) return false;
        const url = editingItem
            ? replacePath(paths.itemUpdate, editingItem.id)
            : replacePath(paths.itemCreate, selectedMenu.value.id);
        try {
            const data = await jsonRequest(url, {
                method: HttpMethod.Post,
                body: JSON.stringify(payload),
            });
            if (data.ok) {
                toast.success(t("shared.common.saved"));
                selectedMenu.value = data.menu;
                await refreshList();
                return true;
            }
            toast.error(t(data.error ?? "common.error"));
        } catch {
            toast.error(t("shared.common.error"));
        }
        return false;
    }

    async function deleteItem(item) {
        try {
            const data = await jsonRequest(
                replacePath(paths.itemDelete, item.id),
                { method: HttpMethod.Post },
            );
            if (data.ok) {
                toast.success(t("shared.common.deleted"));
                selectedMenu.value = data.menu;
                return true;
            }
            toast.error(t("shared.common.error"));
        } catch {
            toast.error(t("shared.common.error"));
        }
        return false;
    }

    return {
        menus,
        selectedMenu,
        loadingMenu,
        selectMenu,
        refreshMenu,
        refreshList,
        updateMenu,
        deleteMenu,
        reorderItems,
        saveItem,
        deleteItem,
    };
}
