<script setup>
import { computed, ref, watch } from "vue";
import { VueDraggable } from "vue-draggable-plus";
import { ChevronDown, ChevronRight, GripVertical, Pencil, Trash2, Plus } from "lucide-vue-next";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";

const props = defineProps({
    node: { type: Object, required: true },
    hierarchical: { type: Boolean, default: false },
    activeLocale: { type: String, required: true },
    groupName: { type: String, required: true },
    collapsed: { type: Object, required: true },
});

const emit = defineEmits(["edit", "delete", "add-child", "toggle-collapse", "end"]);

const name = computed(() => props.node.translations?.[props.activeLocale]?.name
    ?? props.node.translations?.fr?.name
    ?? "(—)");

const isCollapsed = computed(() => props.collapsed.has(props.node.id));
const localChildren = ref([...(props.node.children ?? [])]);
watch(() => props.node.children, (children) => { localChildren.value = [...(children ?? [])]; });
const hasChildren = computed(() => localChildren.value.length > 0);

</script>

<template>
    <div class="border border-line rounded-md bg-surface">
        <div class="flex items-center gap-1 px-2 py-1.5">
            <button
                v-if="hierarchical"
                type="button"
                class="drag-handle cursor-grab active:cursor-grabbing text-muted hover:text-primary p-1"
                :title="'drag'"
            >
                <GripVertical class="w-4 h-4" :stroke-width="2" />
            </button>
            <button
                v-else
                type="button"
                class="drag-handle cursor-grab active:cursor-grabbing text-muted hover:text-primary p-1"
            >
                <GripVertical class="w-4 h-4" :stroke-width="2" />
            </button>

            <AppIconButton
                v-if="hierarchical && hasChildren"
                class="p-0.5"
                v-on:click="emit('toggle-collapse', node.id)"
            >
                <ChevronDown v-if="!isCollapsed" class="w-4 h-4" :stroke-width="2" />
                <ChevronRight v-else class="w-4 h-4" :stroke-width="2" />
            </AppIconButton>
            <span v-else-if="hierarchical" class="w-5" />

            <span class="flex-1 min-w-0 text-sm font-medium text-primary truncate">{{ name }}</span>
            <span class="text-xs text-muted font-mono truncate hidden sm:inline">{{ node.translations?.[activeLocale]?.slug ?? "" }}</span>

            <div class="flex items-center gap-0.5">
                <AppIconButton v-if="hierarchical" color="sky" v-on:click="emit('add-child', node.id)">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
                <AppIconButton color="accent" v-on:click="emit('edit', node)">
                    <Pencil class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
                <AppIconButton color="rose" v-on:click="emit('delete', node)">
                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
            </div>
        </div>

        <VueDraggable
            v-if="hierarchical && !isCollapsed"
            v-model="localChildren"
            :group="{ name: groupName, pull: true, put: true }"
            handle=".drag-handle"
            :animation="150"
            ghost-class="opacity-50"
            class="pl-5 pb-1 space-y-1 min-h-1"
            v-on:end="emit('end')"
        >
            <template v-for="child in localChildren" :key="child.id">
                <TermNode
                    :node="child"
                    :hierarchical="hierarchical"
                    :active-locale="activeLocale"
                    :group-name="groupName"
                    :collapsed="collapsed"
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
