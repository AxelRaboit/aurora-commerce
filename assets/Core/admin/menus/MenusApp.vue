<script setup>
import { useI18n } from "vue-i18n";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import MenuListPanel from "@core/admin/menus/MenuListPanel.vue";
import MenuEditorPanel from "@core/admin/menus/MenuEditorPanel.vue";
import MenuItemModal from "@core/admin/menus/MenuItemModal.vue";
import { useMenuEditor } from "@core/admin/menus/composables/useMenuEditor.js";
import { useMenuEditModal } from "@core/admin/menus/composables/useMenuEditModal.js";
import { useMenuDeleteConfirms } from "@core/admin/menus/composables/useMenuDeleteConfirms.js";
import { useMenuItemModal } from "@core/admin/menus/composables/useMenuItemModal.js";
import { Save } from "lucide-vue-next";

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

const { menus, selectedMenu, selectMenu, updateMenu, deleteMenu, reorderItems, saveItem, deleteItem } =
    useMenuEditor(paths, props.initialMenus);

const { menuModal, menuForm, openEditMenu, submitMenu } = useMenuEditModal(updateMenu);
const { confirmDeleteMenu, submitDeleteMenu, confirmDeleteItem, submitDeleteItem } = useMenuDeleteConfirms(deleteMenu, deleteItem);
const { itemModal, openCreateItem, openEditItem, submitItem } = useMenuItemModal(saveItem);
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
                <AppModalFooter>
                    <AppButton variant="ghost" v-on:click="menuModal.open = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" :loading="menuModal.saving"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="!!confirmDeleteMenu" max-width="sm" v-on:close="confirmDeleteMenu = null">
            <p class="text-sm text-primary">{{ t("admin.menus.deleteConfirm", { name: confirmDeleteMenu?.name ?? "" }) }}</p>
            <div class="flex justify-end gap-2 pt-3">
                <AppButton variant="ghost" v-on:click="confirmDeleteMenu = null">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" v-on:click="submitDeleteMenu">{{ t("shared.common.delete") }}</AppButton>
            </div>
        </AppModal>

        <AppModal :show="!!confirmDeleteItem" max-width="sm" v-on:close="confirmDeleteItem = null">
            <p class="text-sm text-primary">{{ t("admin.menus.deleteItemConfirm") }}</p>
            <div class="flex justify-end gap-2 pt-3">
                <AppButton variant="ghost" v-on:click="confirmDeleteItem = null">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" v-on:click="submitDeleteItem">{{ t("shared.common.delete") }}</AppButton>
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
