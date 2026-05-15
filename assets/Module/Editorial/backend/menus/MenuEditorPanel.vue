<script setup>
/* eslint-disable vue/no-mutating-props -- selectedMenu is owned by parent and mutated in-place by drag-drop */
import { toRef } from "vue";
import { useI18n } from "vue-i18n";
import { VueDraggable } from "vue-draggable-plus";
import { Plus, Trash2, Pencil, ListTree } from "lucide-vue-next";
import { useMenuTree } from "@editorial/backend/menus/composables/useMenuTree.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import MenuItemRow from "@editorial/backend/menus/MenuItemRow.vue";

const { t } = useI18n();

const props = defineProps({
    menu: { type: Object, default: null },
    targetTypes: { type: Array, default: () => [] },
});

const emit = defineEmits([
    "edit-menu",
    "delete-menu",
    "add-item",
    "edit-item",
    "delete-item",
    "reorder-root",
    "reorder-children",
]);

const { itemCount, applyChildrenReorder } = useMenuTree(toRef(props, "menu"));

function onChildReordered({ item, children }) {
    applyChildrenReorder(item, children);
    emit("reorder-children");
}
</script>

<template>
    <main class="bg-surface border border-line rounded-xl">
        <div v-if="!menu" class="flex items-center justify-center h-full p-12">
            <div class="text-center">
                <ListTree class="w-12 h-12 text-muted mx-auto mb-3" :stroke-width="1.5" />
                <p class="text-secondary">{{ t("backend.menus.selectHint") }}</p>
            </div>
        </div>

        <div v-else class="p-5 space-y-5">
            <div class="flex items-start justify-between gap-3 border-b border-line pb-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-primary">{{ menu.name }}</h2>
                    <p class="text-xs text-muted font-mono mt-0.5">{{ menu.location }}</p>
                    <p v-if="menu.description" class="text-xs text-secondary mt-1">{{ menu.description }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <AppBadge v-if="menu.protected" color="amber" :title="t('backend.menus.protectedHint')">
                        {{ t('backend.menus.protected') }}
                    </AppBadge>
                    <AppButton variant="secondary" size="md" v-on:click="$emit('edit-menu', menu)">
                        <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.edit") }}
                    </AppButton>
                    <AppButton
                        v-if="!menu.protected"
                        variant="danger"
                        size="md"
                        v-on:click="$emit('delete-menu', menu)"
                    >
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-secondary uppercase tracking-wide">
                        {{ t("backend.menus.items") }} ({{ itemCount }})
                    </p>
                    <AppButton variant="primary" size="md" v-on:click="$emit('add-item')">
                        <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.add") }}
                    </AppButton>
                </div>

                <AppNoData v-if="!menu.items?.length" :message="t('backend.menus.itemsEmpty')" />

                <VueDraggable
                    v-else
                    v-model="menu.items"
                    handle=".drag-handle"
                    :animation="150"
                    :group="{ name: 'menu-items', pull: true, put: true }"
                    class="space-y-2"
                    v-on:end="$emit('reorder-root')"
                >
                    <MenuItemRow
                        v-for="item in menu.items"
                        :key="item.id"
                        :item="item"
                        :target-types="targetTypes"
                        v-on:edit="$emit('edit-item', $event)"
                        v-on:delete="$emit('delete-item', $event)"
                        v-on:reorder-children="onChildReordered"
                    />
                </VueDraggable>
            </div>
        </div>
    </main>
</template>
