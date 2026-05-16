<script setup>
import { toRef } from "vue";
import { useI18n } from "vue-i18n";
import { useNoteEditorTextarea } from "@notes/backend/markdown/composables/useNoteEditorTextarea.js";
import AppFloatingMenu from "@shared/components/overlay/AppFloatingMenu.vue";
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
        <AppFloatingMenu
            v-if="showSlash && filteredCommands.length > 0"
            :items="filteredCommands"
            :position="slashPosition"
            :active-index="slashIndex"
            v-on:select="selectCommand"
            v-on:highlight="highlightCommand"
        >
            <template #default="{ item }">
                <span class="inline-flex w-6 shrink-0 justify-center font-mono text-xs text-muted">
                    {{ item.icon }}
                </span>
                <span class="flex-1">{{ item.label }}</span>
            </template>
        </AppFloatingMenu>

        <!-- Wiki-link autocomplete ('[[' anywhere on a line) -->
        <AppFloatingMenu
            v-if="showSuggestions && filteredSuggestions.length > 0"
            :items="filteredSuggestions"
            :position="suggestionPosition"
            :active-index="suggestionIndex"
            v-on:select="selectSuggestion"
            v-on:highlight="highlightSuggestion"
        >
            <template #default="{ item }">
                <FileText class="w-3.5 h-3.5 shrink-0 text-muted" :stroke-width="2" />
                <span class="flex-1 truncate">
                    {{ item.title || t('notes.markdown.untitled') }}
                </span>
            </template>
        </AppFloatingMenu>
    </div>
</template>
