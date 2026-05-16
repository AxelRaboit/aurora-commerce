import { ref, nextTick } from "vue";
import { useSlashCommands } from "@notes/backend/markdown/composables/useSlashCommands.js";

/**
 * Wiring for the markdown notes textarea + slash-command palette.
 *
 * Owns the textarea ref, forwards input updates to the parent via the
 * given `emitUpdate(value)` callback, and routes keyboard / blur events
 * through `useSlashCommands`. When a command is picked (Enter / Tab /
 * click), splices its `insert` template into the textarea value and
 * restores the caret on the next tick.
 *
 * Stays a composable so the SFC can be pure presentation: bind
 * `textareaRef` + the returned event handlers + the palette state and
 * the rest is mechanical.
 *
 * @param {object} deps
 * @param {(text: string) => void} deps.emitUpdate
 * @param {(key: string) => string} deps.t
 */
export function useNoteEditorTextarea({ emitUpdate, t }) {
    const textareaRef = ref(null);

    const {
        showSlash,
        slashIndex,
        slashPosition,
        filteredCommands,
        onInput: onSlashInput,
        onKeydown: onSlashKeydown,
        applyCommand,
        closeSlash,
    } = useSlashCommands({ t });

    function onInput(event) {
        emitUpdate(event.target.value);
        onSlashInput(event);
    }

    async function selectCommand(command) {
        const textarea = textareaRef.value;
        if (!textarea) return;
        const { newContent, newCaret } = applyCommand(
            textarea,
            command,
            textarea.value,
        );
        emitUpdate(newContent);
        // Wait for Vue to re-render with the new model value before
        // moving the caret — without this, setSelectionRange would
        // place it on the pre-update content and the user would see
        // a jump on the next keystroke.
        await nextTick();
        textarea.focus();
        textarea.setSelectionRange(newCaret, newCaret);
    }

    function onKeydown(event) {
        const picked = onSlashKeydown(event);
        if (picked) selectCommand(picked);
    }

    function onBlur() {
        // Defer so a mousedown on a palette item still fires before the
        // palette unmounts (click → mousedown ⇒ blur on textarea ⇒
        // immediate close would race the click).
        setTimeout(() => closeSlash(), 150);
    }

    function highlightCommand(index) {
        slashIndex.value = index;
    }

    return {
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
    };
}
