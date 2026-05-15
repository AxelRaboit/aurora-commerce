<script setup>
import { ref } from 'vue';
import { ChevronRight, ChevronDown, FileText, Plus } from 'lucide-vue-next';

const props = defineProps({
    node: { type: Object, required: true },
    selectedId: { type: Number, default: null },
    depth: { type: Number, default: 0 },
});

const emit = defineEmits(['select', 'create-child']);

const expanded = ref(true);
const hasChildren = () => Array.isArray(props.node.children) && props.node.children.length > 0;
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
                v-if="hasChildren()"
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

            <button
                class="opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-line/50 shrink-0"
                :title="$t('notes.markdown.create_child')"
                v-on:click.stop="emit('create-child', node.id)"
            >
                <Plus class="w-3.5 h-3.5" :stroke-width="2" />
            </button>
        </div>

        <ul v-if="hasChildren() && expanded" class="mt-0.5">
            <NoteTreeItem
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                :selected-id="selectedId"
                :depth="depth + 1"
                v-on:select="(id) => emit('select', id)"
                v-on:create-child="(id) => emit('create-child', id)"
            />
        </ul>
    </li>
</template>
