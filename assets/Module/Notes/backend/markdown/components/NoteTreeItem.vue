<script setup>
import { ref, computed } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import { ChevronRight, ChevronDown, FileText, Plus, GripVertical } from 'lucide-vue-next';

const props = defineProps({
    node: { type: Object, required: true },
    selectedId: { type: Number, default: null },
    depth: { type: Number, default: 0 },
    draggable: { type: Boolean, default: false },
    groupName: { type: String, default: 'notes-tree' },
});

const emit = defineEmits(['select', 'create-child', 'drag-start', 'drag-end']);

const expanded = ref(true);
const hasChildren = computed(() => Array.isArray(props.node.children) && props.node.children.length > 0);
</script>

<template>
    <li class="select-none">
        <div
            class="group flex items-center gap-1 px-2 py-1.5 rounded-md cursor-pointer text-sm"
            :class="[
                selectedId === node.id ? 'bg-accent-100 dark:bg-accent-900/30 text-primary' : 'hover:bg-surface-2 text-secondary',
            ]"
            :style="{ paddingLeft: `${depth * 12 + 4}px` }"
            v-on:click="emit('select', node.id)"
        >
            <button
                v-if="hasChildren"
                class="p-0.5 rounded hover:bg-line/50"
                v-on:click.stop="expanded = !expanded"
            >
                <ChevronDown v-if="expanded" class="w-3.5 h-3.5" :stroke-width="2" />
                <ChevronRight v-else class="w-3.5 h-3.5" :stroke-width="2" />
            </button>
            <FileText v-else class="w-3.5 h-3.5 text-muted shrink-0 ml-1" :stroke-width="1.75" />

            <span class="truncate flex-1">
                {{ node.title || $t('notes.markdown.untitled') }}
            </span>

            <span
                v-if="draggable"
                class="drag-handle opacity-0 group-hover:opacity-100 cursor-grab active:cursor-grabbing p-0.5 rounded hover:bg-line/50 shrink-0"
                :title="$t('notes.markdown.drag_handle')"
                v-on:click.stop
            >
                <GripVertical class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
            </span>

            <button
                class="opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-line/50 shrink-0"
                :title="$t('notes.markdown.create_child')"
                v-on:click.stop="emit('create-child', node.id)"
            >
                <Plus class="w-3.5 h-3.5" :stroke-width="2" />
            </button>
        </div>

        <!--
            Children list: always a VueDraggable when drag is enabled, so users
            can drop a node *into* an empty parent (the list serves as a drop
            target). When drag is disabled (filter active) we fall back to a
            plain <ul>.
        -->
        <VueDraggable
            v-if="draggable && expanded"
            v-model="node.children"
            :group="groupName"
            handle=".drag-handle"
            :animation="150"
            ghost-class="opacity-50"
            tag="ul"
            class="mt-0.5 min-h-1"
            v-on:start="emit('drag-start')"
            v-on:end="emit('drag-end')"
        >
            <NoteTreeItem
                v-for="child in node.children"
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

        <ul v-else-if="hasChildren && expanded" class="mt-0.5">
            <NoteTreeItem
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                :selected-id="selectedId"
                :depth="depth + 1"
                :draggable="false"
                v-on:select="(id) => emit('select', id)"
                v-on:create-child="(id) => emit('create-child', id)"
            />
        </ul>
    </li>
</template>
