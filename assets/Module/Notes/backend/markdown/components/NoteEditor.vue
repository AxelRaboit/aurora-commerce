<script setup>
import { toRef } from "vue";
import { useI18n } from "vue-i18n";
import { useNoteEditorTextarea } from "@notes/backend/markdown/composables/useNoteEditorTextarea.js";
import { FileText } from "lucide-vue-next";

const props = defineProps({
    modelValue: { type: String, default: "" },
    placeholder: { type: String, default: "" },
    /**
     * Flat list of the user's notes — fed into the `[[` autocomplete so
     * suggestions reflect the live note list (a newly-created note
     * appears as soon as the parent's notes ref refreshes).
     */
    flatNotes: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:modelValue"]);

const { t } = useI18n();

const {
    textareaRef,
    showSlash,
    slashIndex,
    slashPosition,
    filteredCommands,
    selectCommand,
    highlightCommand,
    showSuggestions,
    suggestionIndex,
    suggestionPosition,
    filteredSuggestions,
    selectSuggestion,
    highlightSuggestion,
    onInput,
    onKeydown,
    onBlur,
} = useNoteEditorTextarea({
    emitUpdate: (value) => emit("update:modelValue", value),
    t,
    flatNotes: toRef(props, "flatNotes"),
    untitledLabel: t("notes.markdown.untitled"),
});
</script>

<template>
    <div class="relative h-full w-full">
        <textarea
            ref="textareaRef"
            :value="modelValue"
            :placeholder="placeholder"
            class="block h-full w-full rounded-md border border-line bg-surface px-3 py-2 text-primary placeholder-muted focus:border-accent-500 focus:ring-1 focus:ring-accent-500 transition resize-none font-mono text-sm"
            rows="20"
            v-on:input="onInput"
            v-on:keydown="onKeydown"
            v-on:blur="onBlur"
        />

        <!-- Slash command palette ('/' at line start) -->
        <div
            v-if="showSlash && filteredCommands.length > 0"
            class="absolute z-30 min-w-56 max-h-64 overflow-auto rounded-md border border-line bg-surface shadow-lg py-1"
            :style="{ top: `${slashPosition.top}px`, left: `${slashPosition.left}px` }"
            v-on:mousedown.prevent
        >
            <button
                v-for="(command, index) in filteredCommands"
                :key="command.id"
                type="button"
                class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm transition-colors"
                :class="
                    index === slashIndex
                        ? 'bg-accent-500/15 text-primary'
                        : 'text-secondary hover:bg-surface-2'
                "
                v-on:mousedown.prevent="selectCommand(command)"
                v-on:mouseenter="highlightCommand(index)"
            >
                <span class="inline-flex w-6 shrink-0 justify-center font-mono text-xs text-muted">
                    {{ command.icon }}
                </span>
                <span class="flex-1">{{ command.label }}</span>
            </button>
        </div>

        <!-- Wiki-link autocomplete ('[[' anywhere on a line) -->
        <div
            v-if="showSuggestions && filteredSuggestions.length > 0"
            class="absolute z-30 min-w-56 max-h-64 overflow-auto rounded-md border border-line bg-surface shadow-lg py-1"
            :style="{ top: `${suggestionPosition.top}px`, left: `${suggestionPosition.left}px` }"
            v-on:mousedown.prevent
        >
            <button
                v-for="(note, index) in filteredSuggestions"
                :key="note.id"
                type="button"
                class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm transition-colors"
                :class="
                    index === suggestionIndex
                        ? 'bg-accent-500/15 text-primary'
                        : 'text-secondary hover:bg-surface-2'
                "
                v-on:mousedown.prevent="selectSuggestion(note)"
                v-on:mouseenter="highlightSuggestion(index)"
            >
                <FileText class="w-3.5 h-3.5 shrink-0 text-muted" :stroke-width="2" />
                <span class="flex-1 truncate">
                    {{ note.title || t('notes.markdown.untitled') }}
                </span>
            </button>
        </div>
    </div>
</template>
