<script setup>
import { ref, computed } from 'vue';
import { ChevronRight, ChevronDown, GripVertical, Plus } from 'lucide-vue-next';
import AppIconButton from '@shared/components/action/AppIconButton.vue';

const props = defineProps({
    node: { type: Object, required: true },
    selectedId: { type: Number, default: null },
    draggable: { type: Boolean, default: false },
    draggingId: { type: Number, default: null },
    dragOverId: { type: Number, default: null },
});

const emit = defineEmits([
    'select',
    'create-child',
    'drag-start',
    'drag-end',
    'drag-over',
    'drag-leave',
    'drop',
]);

const expanded = ref(true);

const children = computed(() => props.node.children ?? []);
const hasChildren = computed(() => children.value.length > 0);
const isSelected = computed(() => props.selectedId === props.node.id);
const isDragOver = computed(() => props.dragOverId === props.node.id);
const isBeingDragged = computed(() => props.draggingId === props.node.id);
</script>

<template>
    <div>
        <div
            class="group flex items-center gap-0.5 px-1.5 py-1 rounded-md cursor-pointer text-sm border border-transparent transition-colors"
            :class="[
                isDragOver ? 'bg-accent-100 dark:bg-accent-900/40 border-accent-500 text-primary ring-1 ring-accent-500/60' :
                isSelected ? 'bg-accent-100 dark:bg-accent-900/30 text-primary border-accent-500/30' :
                'hover:bg-surface-2 text-secondary',
                isBeingDragged ? 'opacity-40' : '',
            ]"
            :draggable="draggable"
            v-on:click="emit('select', node.id)"
            v-on:dragstart="emit('drag-start', node, $event)"
            v-on:dragend="emit('drag-end', $event)"
            v-on:dragover="emit('drag-over', node, $event)"
            v-on:dragleave="emit('drag-leave', node, $event)"
            v-on:drop="emit('drop', node, $event)"
        >
            <span
                v-if="draggable"
                class="shrink-0 text-muted cursor-grab active:cursor-grabbing p-0.5"
                :title="$t('notes.markdown.drag_handle')"
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

        <div v-if="hasChildren && expanded" class="ml-3 pl-3 border-l border-line/40 space-y-0.5">
            <NoteTreeItem
                v-for="child in children"
                :key="child.id"
                :node="child"
                :selected-id="selectedId"
                :draggable="draggable"
                :dragging-id="draggingId"
                :drag-over-id="dragOverId"
                v-on:select="(id) => emit('select', id)"
                v-on:create-child="(id) => emit('create-child', id)"
                v-on:drag-start="(n, e) => emit('drag-start', n, e)"
                v-on:drag-end="(e) => emit('drag-end', e)"
                v-on:drag-over="(n, e) => emit('drag-over', n, e)"
                v-on:drag-leave="(n, e) => emit('drag-leave', n, e)"
                v-on:drop="(n, e) => emit('drop', n, e)"
            />
        </div>
    </div>
</template>
