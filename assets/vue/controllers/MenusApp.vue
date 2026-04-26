<script setup>
import { ref, reactive } from "vue";
import { useI18n } from "vue-i18n";
import AppButton from "@/components/AppButton.vue";
import AppInput from "@/components/AppInput.vue";
import AppModal from "@/components/AppModal.vue";
import MenuListPanel from "@/admin/menus/MenuListPanel.vue";
import MenuEditorPanel from "@/admin/menus/MenuEditorPanel.vue";
import MenuItemModal from "@/admin/menus/MenuItemModal.vue";
import { useMenuEditor } from "@/admin/menus/composables/useMenuEditor.js";

const { t } = useI18n();

const props = defineProps({
    initialMenus: { type: Array, default: () => [] },
    locales: { type: Array, default: () => [] },
    targetTypes: { type: Array, default: () => [] },
    visibilities: { type: Array, default: () => [] },
    listPath: { type: String, required: true },
    createPath: { type: String, required: true },
    showPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    itemCreatePath: { type: String, required: true },
    itemUpdatePath: { type: String, required: true },
    itemDeletePath: { type: String, required: true },
    itemReorderPath: { type: String, required: true },
    pickerPostsPath: { type: String, required: true },
    pickerTermsPath: { type: String, required: true },
    pickerPostTypesPath: { type: String, required: true },
    pickerTaxonomiesPath: { type: String, required: true },
});

const paths = {
    list: props.listPath,
    show: props.showPath,
    update: props.updatePath,
    delete: props.deletePath,
    itemCreate: props.itemCreatePath,
    itemUpdate: props.itemUpdatePath,
    itemDelete: props.itemDeletePath,
    itemReorder: props.itemReorderPath,
};

const {
    menus,
    selectedMenu,
    selectMenu,
    updateMenu,
    deleteMenu,
    reorderItems,
    saveItem,
    deleteItem,
} = useMenuEditor(paths, props.initialMenus);

// ── Menu edit modal ──────────────────────────────────────────────────────────

const menuModal = reactive({ open: false, editing: null, saving: false });
const menuForm = reactive({ name: "", location: "", description: "" });

function openEditMenu(menu) {
    menuModal.editing = menu;
    menuForm.name = menu.name;
    menuForm.location = menu.location;
    menuForm.description = menu.description ?? "";
    menuModal.open = true;
}

async function submitMenu() {
    if (!menuModal.editing) return;
    menuModal.saving = true;
    try {
        const ok = await updateMenu(menuModal.editing, {
            name: menuForm.name,
            location: menuForm.location,
            description: menuForm.description || null,
        });
        if (ok) menuModal.open = false;
    } finally {
        menuModal.saving = false;
    }
}

// ── Delete confirms ──────────────────────────────────────────────────────────

const confirmDeleteMenu = ref(null);
async function submitDeleteMenu() {
    if (!confirmDeleteMenu.value) return;
    if (await deleteMenu(confirmDeleteMenu.value)) {
        confirmDeleteMenu.value = null;
    }
}

const confirmDeleteItem = ref(null);
async function submitDeleteItem() {
    if (!confirmDeleteItem.value) return;
    if (await deleteItem(confirmDeleteItem.value)) {
        confirmDeleteItem.value = null;
    }
}

// ── Item modal ───────────────────────────────────────────────────────────────

const itemModal = reactive({ open: false, editing: null, saving: false });

function openCreateItem() {
    itemModal.editing = null;
    itemModal.open = true;
}

function openEditItem(item) {
    itemModal.editing = item;
    itemModal.open = true;
}

async function submitItem(payload) {
    itemModal.saving = true;
    try {
        const ok = await saveItem(itemModal.editing, payload);
        if (ok) itemModal.open = false;
    } finally {
        itemModal.saving = false;
    }
}
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-4 min-h-150">
        <MenuListPanel
            :menus="menus"
            :selected-id="selectedMenu?.id ?? null"
            v-on:select="selectMenu"
        />

        <MenuEditorPanel
            :menu="selectedMenu"
            :target-types="targetTypes"
            v-on:edit-menu="openEditMenu"
            v-on:delete-menu="confirmDeleteMenu = $event"
            v-on:add-item="openCreateItem"
            v-on:edit-item="openEditItem"
            v-on:delete-item="confirmDeleteItem = $event"
            v-on:reorder-root="reorderItems"
            v-on:reorder-children="reorderItems"
        />

        <AppModal :show="menuModal.open" max-width="md" v-on:close="menuModal.open = false">
            <h3 class="text-lg font-bold text-primary mb-4">{{ t("admin.menus.editMenu") }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitMenu">
                <AppInput v-model="menuForm.name" :label="t('admin.menus.name')" required />
                <AppInput
                    v-model="menuForm.location"
                    :label="t('admin.menus.location')"
                    :placeholder="t('admin.menus.locationPlaceholder')"
                    :readonly="menuModal.editing?.protected"
                    required
                />
                <p v-if="menuModal.editing?.protected" class="text-xs text-amber-500 -mt-2">{{ t('admin.menus.locationLockedHint') }}</p>
                <AppInput v-model="menuForm.description" :label="t('admin.menus.description')" />
                <div class="flex justify-end gap-2 pt-2">
                    <AppButton variant="ghost" v-on:click="menuModal.open = false">{{ t("common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" :loading="menuModal.saving">{{ t("common.save") }}</AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal :show="!!confirmDeleteMenu" max-width="sm" v-on:close="confirmDeleteMenu = null">
            <p class="text-sm text-primary">{{ t("admin.menus.deleteConfirm", { name: confirmDeleteMenu?.name ?? "" }) }}</p>
            <div class="flex justify-end gap-2 pt-3">
                <AppButton variant="ghost" v-on:click="confirmDeleteMenu = null">{{ t("common.cancel") }}</AppButton>
                <AppButton variant="danger" v-on:click="submitDeleteMenu">{{ t("common.delete") }}</AppButton>
            </div>
        </AppModal>

        <AppModal :show="!!confirmDeleteItem" max-width="sm" v-on:close="confirmDeleteItem = null">
            <p class="text-sm text-primary">{{ t("admin.menus.deleteItemConfirm") }}</p>
            <div class="flex justify-end gap-2 pt-3">
                <AppButton variant="ghost" v-on:click="confirmDeleteItem = null">{{ t("common.cancel") }}</AppButton>
                <AppButton variant="danger" v-on:click="submitDeleteItem">{{ t("common.delete") }}</AppButton>
            </div>
        </AppModal>

        <MenuItemModal
            :show="itemModal.open"
            :editing="itemModal.editing"
            :target-types="targetTypes"
            :visibilities="visibilities"
            :locales="locales"
            :picker-posts-path="pickerPostsPath"
            :picker-terms-path="pickerTermsPath"
            :picker-post-types-path="pickerPostTypesPath"
            :picker-taxonomies-path="pickerTaxonomiesPath"
            v-on:close="itemModal.open = false"
            v-on:save="submitItem"
        />
    </div>
</template>
