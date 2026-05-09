<script setup>
import { ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { VueDraggable } from "vue-draggable-plus";
import { GripVertical, Pencil, Trash2, ChevronRight, ExternalLink, EyeOff, Eye } from "lucide-vue-next";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";

const props = defineProps({
    item: { type: Object, required: true },
    targetTypes: { type: Array, default: () => [] },
});

const emit = defineEmits(["edit", "delete", "reorder-children"]);

const { t } = useI18n();
const expanded = ref(true);
const localChildren = ref([...(props.item.children ?? [])]);

watch(() => props.item.children, (val) => { localChildren.value = [...(val ?? [])]; });

function visibilityIcon(item) {
    if (item.visibility === "guests_only" || item.visibility === "authenticated_only") return EyeOff;
    return Eye;
}

</script>

<template>
    <div class="bg-surface border border-line/60 rounded-lg">
        <div class="flex items-center gap-2 px-3 py-2.5">
            <GripVertical class="drag-handle w-4 h-4 text-muted cursor-grab active:cursor-grabbing shrink-0" :stroke-width="2" />

            <AppIconButton
                v-if="item.children?.length"
                class="shrink-0"
                :title="expanded ? t('shared.common.collapse') : t('shared.common.expand')"
                v-on:click="expanded = !expanded"
            >
                <ChevronRight class="w-4 h-4 text-muted transition-transform" :class="{ 'rotate-90': expanded }" :stroke-width="2" />
            </AppIconButton>
            <div v-else class="w-5 shrink-0" />

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-medium text-primary truncate" :class="{ 'text-rose-400': item.targetPreview?.missing }">
                        {{ item.targetPreview?.label ?? "—" }}
                    </span>
                    <AppBadge v-if="item.translations?.fr || item.translations?.en" color="accent" class="shrink-0">
                        {{ Object.keys(item.translations).filter((l) => item.translations[l]).join(", ") }}
                    </AppBadge>
                    <AppBadge v-if="item.openInNewTab" color="gray" class="shrink-0">
                        <ExternalLink class="w-3 h-3" :stroke-width="2.5" />
                    </AppBadge>
                    <AppBadge v-if="item.visibility !== 'always'" color="amber" class="shrink-0">
                        <component :is="visibilityIcon(item)" class="w-3 h-3" :stroke-width="2.5" />
                    </AppBadge>
                </div>
                <p v-if="item.targetPreview?.hint" class="text-xs text-muted truncate font-mono mt-0.5">{{ item.targetPreview.hint }}</p>
            </div>

            <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="emit('edit', item)">
                <Pencil class="w-4 h-4" :stroke-width="2" />
            </AppIconButton>
            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="emit('delete', item)">
                <Trash2 class="w-4 h-4" :stroke-width="2" />
            </AppIconButton>
        </div>

        <div v-if="expanded && item.children?.length" class="pl-8 pr-3 pb-2 space-y-2">
            <VueDraggable
                v-model="localChildren"
                handle=".drag-handle"
                :animation="150"
                :group="{ name: 'menu-items', pull: true, put: true }"
                class="space-y-2"
                v-on:end="emit('reorder-children', { item: props.item, children: localChildren })"
            >
                <MenuItemRow
                    v-for="child in item.children"
                    :key="child.id"
                    :item="child"
                    :target-types="targetTypes"
                    v-on:edit="emit('edit', $event)"
                    v-on:delete="emit('delete', $event)"
                    v-on:reorder-children="emit('reorder-children', $event)"
                />
            </VueDraggable>
        </div>
    </div>
</template>
