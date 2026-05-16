<script setup>
import { ref, toRef, watch, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { useNoteEditorTextarea } from "@notes/backend/markdown/composables/useNoteEditorTextarea.js";
import AppFloatingMenu from "@shared/components/overlay/AppFloatingMenu.vue";
import AppSearchInput from "@shared/components/form/input/AppSearchInput.vue";
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
    suggestionQuery,
    suggestionIndex,
    suggestionPosition,
    filteredSuggestions,
    selectSuggestion,
    highlightSuggestion,
    onSearchKeydown,
    onSearchBlur,
    onInput,
    onKeydown,
    onBlur,
} = useNoteEditorTextarea({
    emitUpdate: (value) => emit("update:modelValue", value),
    t,
    flatNotes: toRef(props, "flatNotes"),
    untitledLabel: t("notes.markdown.untitled"),
});

// Refocus the search input when the wiki popover opens so the user
// can keep typing without having to click the bar manually.
const searchInputRef = ref(null);
watch(showSuggestions, async (open) => {
    if (!open) return;
    await nextTick();
    searchInputRef.value?.focus();
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
            v-if="showSlash"
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
            <template #empty>
                {{ t('notes.markdown.slash.no_results') }}
            </template>
        </AppFloatingMenu>

        <!-- Wiki-link autocomplete ('[[' anywhere on a line) — header
             shows the inline filter so the user sees "what they're
             searching for". Keystrokes still go into the textarea (the
             actual source of truth) so this stays a passive display. -->
        <AppFloatingMenu
            v-if="showSuggestions"
            :items="filteredSuggestions"
            :position="suggestionPosition"
            :active-index="suggestionIndex"
            v-on:select="selectSuggestion"
            v-on:highlight="highlightSuggestion"
        >
            <template #header>
                <!-- Real, focusable search field. `AppSearchInput` is our
                     shared search-bar component (built on `AppInput` with
                     a magnifier prefix and a clearable X). We disable
                     debouncing because filtering must feel instant, and
                     listen on `focusout` rather than `blur` because blur
                     events don't bubble through the wrapping <div>. -->
                <div class="p-2">
                    <AppSearchInput
                        ref="searchInputRef"
                        v-model="suggestionQuery"
                        :placeholder="t('notes.markdown.wiki_search_placeholder')"
                        :debounce="0"
                        :clearable="false"
                        v-on:keydown="onSearchKeydown"
                        v-on:focusout="onSearchBlur"
                    />
                </div>
            </template>
            <template #default="{ item }">
                <FileText class="w-3.5 h-3.5 shrink-0 text-muted" :stroke-width="2" />
                <span class="flex-1 truncate">
                    {{ item.title || t('notes.markdown.untitled') }}
                </span>
            </template>
            <template #empty>
                {{ t('notes.markdown.wiki_no_results', { query: suggestionQuery }) }}
            </template>
        </AppFloatingMenu>
    </div>
</template>
