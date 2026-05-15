<script setup>
import { computed } from "vue";
import { ChevronDown, ChevronRight, GripVertical, Pencil, Trash2, Plus } from "lucide-vue-next";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppButton from "@/shared/components/action/AppButton.vue";

const props = defineProps({
    node: { type: Object, required: true },
    hierarchical: { type: Boolean, default: false },
    activeLocale: { type: String, required: true },
    collapsed: { type: Object, required: true },
    draggingId: { type: Number, default: null },
    dragOverId: { type: Number, default: null },
});

const emit = defineEmits([
    "edit",
    "delete",
    "add-child",
    "toggle-collapse",
    "term-drag-start",
    "term-drag-end",
    "term-drag-over",
    "term-drag-leave",
    "drop-on-term",
]);

const name = computed(
    () =>
        props.node.translations?.[props.activeLocale]?.name
        ?? props.node.translations?.fr?.name
        ?? "(—)",
);

const isCollapsed = computed(() => props.collapsed.has(props.node.id));
const children = computed(() => props.node.children ?? []);
const hasChildren = computed(() => children.value.length > 0);
const isDragOver = computed(() => props.dragOverId === props.node.id);
const isBeingDragged = computed(() => props.draggingId === props.node.id);
</script>

<template>
    <div
        class="border rounded-md bg-surface transition-colors"
        :class="[
            isDragOver ? 'border-accent-500 ring-1 ring-accent-500/60 bg-accent-50 dark:bg-accent-900/30' :
            'border-line',
            isBeingDragged ? 'opacity-40' : '',
        ]"
        :draggable="true"
        v-on:dragstart.stop="emit('term-drag-start', node, $event)"
        v-on:dragend="emit('term-drag-end', $event)"
        v-on:dragover="emit('term-drag-over', node, $event)"
        v-on:dragleave="emit('term-drag-leave', node, $event)"
        v-on:drop="emit('drop-on-term', node, $event)"
    >
        <div class="flex items-center gap-1 px-2 py-1.5">
            <AppButton
                variant="ghost"
                size="none"
                class="cursor-grab active:cursor-grabbing text-muted hover:text-primary p-1"
                :title="'drag'"
            >
                <GripVertical class="w-4 h-4" :stroke-width="2" />
            </AppButton>

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

        <!--
            Children container — plain stack, no VueDraggable. Each row is its
            own drop target via native HTML5 DnD, so this just provides the
            indented visual layout.
        -->
        <div v-if="hierarchical && !isCollapsed && hasChildren" class="pl-5 pb-1 space-y-1">
            <TermNode
                v-for="child in children"
                :key="child.id"
                :node="child"
                :hierarchical="hierarchical"
                :active-locale="activeLocale"
                :collapsed="collapsed"
                :dragging-id="draggingId"
                :drag-over-id="dragOverId"
                v-on:toggle-collapse="emit('toggle-collapse', $event)"
                v-on:edit="emit('edit', $event)"
                v-on:delete="emit('delete', $event)"
                v-on:add-child="emit('add-child', $event)"
                v-on:term-drag-start="(n, e) => emit('term-drag-start', n, e)"
                v-on:term-drag-end="(e) => emit('term-drag-end', e)"
                v-on:term-drag-over="(n, e) => emit('term-drag-over', n, e)"
                v-on:term-drag-leave="(n, e) => emit('term-drag-leave', n, e)"
                v-on:drop-on-term="(n, e) => emit('drop-on-term', n, e)"
            />
        </div>
    </div>
</template>
