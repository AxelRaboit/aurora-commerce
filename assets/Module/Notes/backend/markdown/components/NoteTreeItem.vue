<script setup>
import { ref, computed } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import { ChevronRight, ChevronDown, GripVertical, Plus } from 'lucide-vue-next';
import AppIconButton from '@shared/components/action/AppIconButton.vue';

const props = defineProps({
    node: { type: Object, required: true },
    selectedId: { type: Number, default: null },
    depth: { type: Number, default: 0 },
    draggable: { type: Boolean, default: false },
    groupName: { type: String, default: 'notes-tree' },
});

const emit = defineEmits(['select', 'create-child', 'drag-start', 'drag-end', 'update:node']);

const expanded = ref(true);

/**
 * Computed children with get/set — matches the ListingCategoryNode
 * pattern. The getter returns the live array (shared reference with
 * tree.value), so VueDraggable's in-place mutations propagate up. The
 * setter is a safety net for the rare case where the lib replaces the
 * array wholesale.
 */
const children = computed({
    get: () => props.node.children ?? [],
    set: (value) => emit('update:node', { ...props.node, children: value }),
});
const hasChildren = computed(() => children.value.length > 0);
const isSelected = computed(() => props.selectedId === props.node.id);
</script>

<template>
    <div>
        <div
            class="group flex items-center gap-0.5 px-1.5 py-1 rounded-md cursor-pointer text-sm border border-transparent"
            :class="[
                isSelected ? 'bg-accent-100 dark:bg-accent-900/30 text-primary border-accent-500/30' : 'hover:bg-surface-2 text-secondary',
            ]"
            v-on:click="emit('select', node.id)"
        >
            <span
                v-if="draggable"
                class="drag-handle cursor-grab active:cursor-grabbing p-0.5 rounded text-muted hover:text-primary hover:bg-line/50 shrink-0"
                :title="$t('notes.markdown.drag_handle')"
                v-on:click.stop
            >
                <GripVertical class="w-3.5 h-3.5" :stroke-width="2" />
            </span>

            <AppIconButton
                v-if="hasChildren"
                size="sm"
                variant="ghost"
                class="p-0.5"
                v-on:click.stop="expanded = !expanded"
            >
                <ChevronDown v-if="expanded" class="w-3.5 h-3.5" :stroke-width="2" />
                <ChevronRight v-else class="w-3.5 h-3.5" :stroke-width="2" />
            </AppIconButton>
            <span v-else class="w-5 shrink-0" />

            <span class="truncate flex-1">
                {{ node.title || $t('notes.markdown.untitled') }}
            </span>

            <AppIconButton
                size="sm"
                variant="ghost"
                class="opacity-0 group-hover:opacity-100 shrink-0"
                :title="$t('notes.markdown.create_child')"
                v-on:click.stop="emit('create-child', node.id)"
            >
                <Plus class="w-3.5 h-3.5" :stroke-width="2" />
            </AppIconButton>
        </div>

        <!--
            Children container — kept compact at rest (no extra vertical
            space between siblings) and grown only on .drag-active body
            class, which we toggle while a drag is in flight. emptyInsertThreshold
            extends Sortable's drop-target detection beyond the visible
            bounds so the user doesn't have to aim precisely at a 1px
            empty container.
        -->
        <VueDraggable
            v-if="draggable && expanded"
            v-model="children"
            :group="{ name: groupName, pull: true, put: true }"
            handle=".drag-handle"
            :animation="150"
            ghost-class="opacity-50"
            :empty-insert-threshold="24"
            tag="div"
            class="notes-children ml-3 pl-3 border-l border-line/40 space-y-0.5 transition-[min-height,border-color,background-color]"
            :class="hasChildren ? '' : 'notes-children-empty'"
            v-on:start="emit('drag-start')"
            v-on:end="emit('drag-end')"
        >
            <NoteTreeItem
                v-for="child in children"
                :key="child.id"
                :node="child"
                :selected-id="selectedId"
                :depth="depth + 1"
                :draggable="draggable"
                :group-name="groupName"
                v-on:select="(id) => emit('select', id)"
                v-on:create-child="(id) => emit('create-child', id)"
                v-on:drag-start="emit('drag-start')"
                v-on:drag-end="emit('drag-end')"
            />
        </VueDraggable>

        <div v-else-if="hasChildren && expanded" class="ml-3 pl-3 border-l border-line/40 space-y-0.5">
            <NoteTreeItem
                v-for="child in children"
                :key="child.id"
                :node="child"
                :selected-id="selectedId"
                :depth="depth + 1"
                :draggable="false"
                v-on:select="(id) => emit('select', id)"
                v-on:create-child="(id) => emit('create-child', id)"
            />
        </div>
    </div>
</template>

<!--
    Non-scoped: body.notes-dragging is set on the document, the children
    container lives inside the component. Scoping the rules would mismatch
    the global body class.
-->
<style>
/* At rest, empty children container takes no vertical space — tree stays compact. */
.notes-children-empty {
    min-height: 2px;
    border-left-color: transparent !important;
}

/* During an active drag, every empty children container grows so users
   can clearly aim 'drop as a child here'. The hover state highlights
   the specific drop target in accent. */
body.notes-dragging .notes-children-empty {
    min-height: 1.75rem;
    border-left-color: var(--color-line, #e5e7eb) !important;
    background-color: rgba(127, 127, 127, 0.04);
}

body.notes-dragging .notes-children-empty:hover {
    border-left-color: var(--color-accent-500, #0ea5e9) !important;
    background-color: rgba(14, 165, 233, 0.08);
}
</style>
