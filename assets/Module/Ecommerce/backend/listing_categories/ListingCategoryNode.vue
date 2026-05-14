<script setup>
import { computed } from "vue";
import { VueDraggable } from "vue-draggable-plus";
import { ChevronDown, ChevronRight, GripVertical, Pencil, Trash2, Plus, EyeOff } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppImage from "@/shared/components/display/AppImage.vue";

const { t } = useI18n();

const props = defineProps({
    node: { type: Object, required: true },
    activeLocale: { type: String, required: true },
    groupName: { type: String, required: true },
    collapsed: { type: Object, required: true },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
});

const emit = defineEmits(["edit", "delete", "add-child", "toggle-collapse", "end"]);

const displayName = computed(() => {
    const translation = props.node.translations?.[props.activeLocale];
    if (translation?.name) return translation.name;
    const firstTranslation = Object.values(props.node.translations ?? {})[0];
    return firstTranslation?.name ?? `#${props.node.id}`;
});

const isCollapsed = computed(() => props.collapsed.has(props.node.id));

const children = computed({
    get: () => props.node.children ?? [],
    set: (value) => { props.node.children = value; },
});
const hasChildren = computed(() => children.value.length > 0);
</script>

<template>
    <div class="border border-line rounded-md bg-surface">
        <div class="flex items-center gap-1 px-2 py-1.5">
            <AppButton
                variant="ghost"
                size="none"
                class="drag-handle cursor-grab active:cursor-grabbing text-muted hover:text-primary p-1"
                :title="'drag'"
            >
                <GripVertical class="w-4 h-4" :stroke-width="2" />
            </AppButton>

            <AppIconButton
                v-if="hasChildren"
                class="p-0.5"
                v-on:click="emit('toggle-collapse', node.id)"
            >
                <ChevronDown v-if="!isCollapsed" class="w-4 h-4" :stroke-width="2" />
                <ChevronRight v-else class="w-4 h-4" :stroke-width="2" />
            </AppIconButton>
            <span v-else class="w-5" />

            <div v-if="node.image" class="w-7 h-7 rounded bg-surface-2 overflow-hidden shrink-0 flex items-center justify-center">
                <AppImage :src="node.image.url" :alt="node.image.alt ?? ''" object-fit="cover" />
            </div>

            <span class="flex-1 min-w-0 text-sm font-medium text-primary truncate">{{ displayName }}</span>

            <AppBadge v-if="!node.isVisible" color="slate">
                <EyeOff class="w-3 h-3" :stroke-width="2" />
                {{ t('backend.ecommerce.listing_categories.hidden') }}
            </AppBadge>

            <div class="flex items-center gap-0.5">
                <AppIconButton v-if="canEdit" color="sky" v-on:click="emit('add-child', node.id)">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
                <AppIconButton v-if="canEdit" color="accent" v-on:click="emit('edit', node)">
                    <Pencil class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
                <AppIconButton v-if="canDelete" color="rose" v-on:click="emit('delete', node)">
                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
            </div>
        </div>

        <VueDraggable
            v-if="!isCollapsed"
            v-model="children"
            :group="{ name: groupName, pull: true, put: true }"
            handle=".drag-handle"
            :animation="150"
            ghost-class="opacity-50"
            class="pl-5 pb-1 space-y-1 min-h-1"
            v-on:end="emit('end')"
        >
            <template v-for="child in children" :key="child.id">
                <ListingCategoryNode
                    :node="child"
                    :active-locale="activeLocale"
                    :group-name="groupName"
                    :collapsed="collapsed"
                    :can-edit="canEdit"
                    :can-delete="canDelete"
                    v-on:toggle-collapse="emit('toggle-collapse', $event)"
                    v-on:edit="emit('edit', $event)"
                    v-on:delete="emit('delete', $event)"
                    v-on:add-child="emit('add-child', $event)"
                    v-on:end="emit('end')"
                />
            </template>
        </VueDraggable>
    </div>
</template>
