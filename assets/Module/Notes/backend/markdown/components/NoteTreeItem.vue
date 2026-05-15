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
 * Computed children with get/set — same pattern as ListingCategoryNode.
 * Reading returns the live array (shared reference with the parent tree,
 * so VueDraggable's in-place splice/push mutations propagate to
 * tree.value automatically). The setter is a safety net for the rare
 * case where vue-draggable-plus replaces the array entirely.
 */
const children = computed({
    get: () => props.node.children ?? [],
    set: (value) => emit('update:node', { ...props.node, children: value }),
});
const hasChildren = computed(() => children.value.length > 0);
const isSelected = computed(() => props.selectedId === props.node.id);
</script>

<template>
    <div class="space-y-0.5">
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
            Children container — always rendered as a VueDraggable when drag
            is on, so empty parents are still valid drop targets. The pl-5
            indent + persistent left border give a visible drop zone even
            when no children exist; the zone grows on drag-over to make
            'drop as child of this note' obvious.
        -->
        <VueDraggable
            v-if="draggable && expanded"
            v-model="children"
            :group="{ name: groupName, pull: true, put: true }"
            handle=".drag-handle"
            :animation="150"
            ghost-class="opacity-50"
            drag-class="drag-active"
            tag="div"
            class="ml-3 pl-3 border-l border-line/40 space-y-0.5 transition-[min-height,border-color] py-0.5"
            :class="hasChildren ? 'min-h-[1.5rem]' : 'min-h-[2rem] hover:border-accent-500/60 hover:bg-accent-50/30 dark:hover:bg-accent-900/10'"
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
