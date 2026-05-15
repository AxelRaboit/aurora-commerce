<script setup>
import { computed } from 'vue';
import { useMarkdownRenderer } from '@notes/backend/markdown/composables/useMarkdownRenderer.js';

const props = defineProps({
    content: { type: String, default: '' },
    noteTitles: { type: Array, default: () => [] }, // list<{id, title}>, used to resolve wiki-link clicks
});

const emit = defineEmits(['wiki-link-click', 'checkbox-toggle']);

const { render } = useMarkdownRenderer();
const html = computed(() => render(props.content));

function onClick(event) {
    const target = event.target;
    if (!target) return;

    // Wiki-link navigation
    if (target.tagName === 'A' && target.classList.contains('wiki-link')) {
        event.preventDefault();
        const noteTitle = target.dataset.noteTitle ?? '';
        const heading = target.dataset.heading ?? '';
        const match = props.noteTitles.find(
            (n) => (n.title ?? '').toLowerCase() === noteTitle.toLowerCase(),
        );
        emit('wiki-link-click', { noteTitle, heading, matchedId: match?.id ?? null });
        return;
    }

    // Interactive checkbox toggle
    if (target.tagName === 'INPUT' && target.classList.contains('task-checkbox')) {
        // Prevent the browser's natural toggle — we round-trip through the source so the
        // checked state is authoritative server-side.
        event.preventDefault();
        const index = Number.parseInt(target.dataset.checkboxIndex ?? '-1', 10);
        if (Number.isInteger(index) && index >= 0) {
            emit('checkbox-toggle', index);
        }
    }
}
</script>

<template>
    <div
        class="note-preview prose prose-sm dark:prose-invert max-w-none"
        v-on:click="onClick"
        v-html="html"
    ></div>
</template>

<style scoped>
.note-preview :deep(.wiki-link) {
    color: var(--color-accent-600, #0ea5e9);
    text-decoration: underline;
    text-decoration-style: dashed;
    text-underline-offset: 3px;
    cursor: pointer;
}

.note-preview :deep(.wiki-link:hover) {
    background: color-mix(in srgb, var(--color-accent-500, #0ea5e9) 12%, transparent);
    border-radius: 2px;
}

.note-preview :deep(.task-list-item) {
    list-style: none;
    margin-left: -1.5rem;
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
}

.note-preview :deep(.task-checkbox) {
    cursor: pointer;
    margin-top: 0.25rem;
}

.note-preview :deep(.callout) {
    border-left: 4px solid var(--callout-color, #6b7280);
    background: color-mix(in srgb, var(--callout-color, #6b7280) 8%, transparent);
    border-radius: 4px;
    padding: 0.75rem 1rem;
    margin: 1rem 0;
}

.note-preview :deep(.callout-header) {
    font-weight: 600;
    color: var(--callout-color, #6b7280);
    margin-bottom: 0.25rem;
}

.note-preview :deep(.callout-body) {
    color: var(--color-text-primary);
}

.note-preview :deep(.callout-body > *:first-child) {
    margin-top: 0;
}

.note-preview :deep(.callout-body > *:last-child) {
    margin-bottom: 0;
}

.note-preview :deep(.callout-note) { --callout-color: #6b7280; }
.note-preview :deep(.callout-info), .note-preview :deep(.callout-abstract), .note-preview :deep(.callout-summary) { --callout-color: #0ea5e9; }
.note-preview :deep(.callout-tip), .note-preview :deep(.callout-hint), .note-preview :deep(.callout-success) { --callout-color: #10b981; }
.note-preview :deep(.callout-warning), .note-preview :deep(.callout-caution) { --callout-color: #f59e0b; }
.note-preview :deep(.callout-danger), .note-preview :deep(.callout-bug), .note-preview :deep(.callout-failure) { --callout-color: #ef4444; }
.note-preview :deep(.callout-question), .note-preview :deep(.callout-faq), .note-preview :deep(.callout-todo) { --callout-color: #8b5cf6; }
.note-preview :deep(.callout-quote), .note-preview :deep(.callout-example) { --callout-color: #94a3b8; }
</style>
