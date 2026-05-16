<script setup>
import { useI18n } from "vue-i18n";
import { useNoteEditorTextarea } from "@notes/backend/markdown/composables/useNoteEditorTextarea.js";

defineProps({
    modelValue: { type: String, default: "" },
    placeholder: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue"]);

const { t } = useI18n();

const {
    textareaRef,
    showSlash,
    slashIndex,
    slashPosition,
    filteredCommands,
    onInput,
    onKeydown,
    onBlur,
    selectCommand,
    highlightCommand,
} = useNoteEditorTextarea({
    emitUpdate: (value) => emit("update:modelValue", value),
    t,
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
    </div>
</template>
