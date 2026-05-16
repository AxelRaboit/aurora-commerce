import { ref, computed } from "vue";

/**
 * Slash-command palette state for the markdown textarea.
 *
 * Detection: typing `/` at the start of a line opens the palette. Anything
 * typed after `/` filters the command list by label or id. Selecting a
 * command (Enter / Tab / click) replaces the `/query` text with the
 * command's `insert` template and positions the caret at `cursorOffset`
 * when defined (otherwise after the inserted snippet).
 *
 * The composable is intentionally view-agnostic: it returns reactive state
 * + handlers that a Vue component wires onto a textarea ref. The palette
 * UI lives in `NoteEditor.vue`.
 *
 * Ported from Onyx (`resources/js/composables/notes/useSlashCommands.js`)
 * with translatable labels and a slimmed-down command set.
 */

const COMMANDS = [
    {
        id: "h1",
        labelKey: "notes.markdown.slash.h1",
        icon: "H1",
        insert: "# ",
        type: "line",
    },
    {
        id: "h2",
        labelKey: "notes.markdown.slash.h2",
        icon: "H2",
        insert: "## ",
        type: "line",
    },
    {
        id: "h3",
        labelKey: "notes.markdown.slash.h3",
        icon: "H3",
        insert: "### ",
        type: "line",
    },
    {
        id: "bullet",
        labelKey: "notes.markdown.slash.bullet",
        icon: "•",
        insert: "- ",
        type: "line",
    },
    {
        id: "numbered",
        labelKey: "notes.markdown.slash.numbered",
        icon: "1.",
        insert: "1. ",
        type: "line",
    },
    {
        id: "checkbox",
        labelKey: "notes.markdown.slash.checkbox",
        icon: "☐",
        insert: "- [ ] ",
        type: "line",
    },
    {
        id: "quote",
        labelKey: "notes.markdown.slash.quote",
        icon: "❝",
        insert: "> ",
        type: "line",
    },
    {
        id: "divider",
        labelKey: "notes.markdown.slash.divider",
        icon: "—",
        insert: "\n---\n",
        type: "block",
    },
    {
        id: "code",
        labelKey: "notes.markdown.slash.code",
        icon: "</>",
        insert: "```\n\n```",
        type: "block",
        cursorOffset: 4,
    },
    {
        id: "callout",
        labelKey: "notes.markdown.slash.callout",
        icon: "!",
        insert: "> [!info] \n> ",
        type: "block",
        cursorOffset: 10,
    },
    {
        id: "link",
        labelKey: "notes.markdown.slash.link",
        icon: "[[",
        insert: "[[]]",
        type: "inline",
        cursorOffset: 2,
    },
    {
        id: "bold",
        labelKey: "notes.markdown.slash.bold",
        icon: "B",
        insert: "****",
        type: "inline",
        cursorOffset: 2,
    },
    {
        id: "italic",
        labelKey: "notes.markdown.slash.italic",
        icon: "I",
        insert: "**",
        type: "inline",
        cursorOffset: 1,
    },
    {
        id: "strikethrough",
        labelKey: "notes.markdown.slash.strikethrough",
        icon: "S̶",
        insert: "~~~~",
        type: "inline",
        cursorOffset: 2,
    },
    {
        id: "table",
        labelKey: "notes.markdown.slash.table",
        icon: "⊞",
        insert: "| Column 1 | Column 2 |\n| --- | --- |\n| Cell | Cell |\n",
        type: "block",
    },
];

export function useSlashCommands({ t }) {
    const showSlash = ref(false);
    const slashQuery = ref("");
    const slashIndex = ref(0);
    const slashPosition = ref({ top: 0, left: 0 });
    // Offset in the textarea value where the user's `/` starts. We
    // replace the substring [slashStart, caret] when a command is picked.
    const slashStart = ref(null);

    const commands = COMMANDS.map((command) => ({
        ...command,
        label: t(command.labelKey),
    }));

    const filteredCommands = computed(() => {
        const query = slashQuery.value.toLowerCase();
        if (query === "") return commands;
        return commands.filter(
            (command) =>
                command.label.toLowerCase().includes(query) ||
                command.id.includes(query),
        );
    });

    /**
     * Inspect the textarea on every input. Opens the palette when the
     * current line starts with `/` and tracks the query (chars after
     * `/`). Anything else closes it.
     */
    function onInput(event) {
        const textarea = event.target;
        const caret = textarea.selectionStart;
        const text = textarea.value;
        const before = text.slice(0, caret);

        const lineStart = before.lastIndexOf("\n") + 1;
        const lineContent = before.slice(lineStart);

        if (lineContent.startsWith("/")) {
            slashStart.value = lineStart;
            slashQuery.value = lineContent.slice(1);
            slashIndex.value = 0;
            showSlash.value = true;
            positionDropdown(textarea, lineStart);
            return;
        }

        closeSlash();
    }

    /**
     * Mirror-div trick to find the on-screen pixel coordinates of the
     * `/` character inside the textarea. Builds an off-screen div with
     * the same font / padding / width, fills it with the text up to the
     * marker, and reads the rect of a `|` placed where the caret is.
     */
    function positionDropdown(textarea, startIndex) {
        const text = textarea.value.substring(0, startIndex);
        const mirror = document.createElement("div");
        const style = window.getComputedStyle(textarea);

        mirror.style.position = "absolute";
        mirror.style.visibility = "hidden";
        mirror.style.whiteSpace = "pre-wrap";
        mirror.style.overflowWrap = "break-word";
        mirror.style.width = style.width;
        mirror.style.font = style.font;
        mirror.style.letterSpacing = style.letterSpacing;
        mirror.style.padding = style.padding;
        mirror.style.lineHeight = style.lineHeight;
        mirror.style.boxSizing = style.boxSizing;
        mirror.style.border = style.border;

        mirror.textContent = text;
        const marker = document.createElement("span");
        marker.textContent = "|";
        mirror.appendChild(marker);

        document.body.appendChild(mirror);

        const textareaRect = textarea.getBoundingClientRect();
        const markerRect = marker.getBoundingClientRect();
        const mirrorRect = mirror.getBoundingClientRect();

        // Position relative to the textarea (the palette is positioned
        // absolute inside the editor wrapper that contains the textarea).
        slashPosition.value = {
            top: markerRect.top - mirrorRect.top - textarea.scrollTop + 24,
            left: markerRect.left - mirrorRect.left,
        };

        document.body.removeChild(mirror);
        void textareaRect;
    }

    /**
     * Keydown intercept. Returns a command when the user confirms (Enter
     * or Tab) so the caller can apply it, or null otherwise. Arrow keys
     * navigate the list, Escape closes.
     */
    function onKeydown(event) {
        if (!showSlash.value) return null;

        if (event.key === "ArrowDown") {
            event.preventDefault();
            slashIndex.value = Math.min(
                slashIndex.value + 1,
                filteredCommands.value.length - 1,
            );
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            slashIndex.value = Math.max(slashIndex.value - 1, 0);
        } else if (event.key === "Enter" || event.key === "Tab") {
            if (filteredCommands.value.length > 0) {
                event.preventDefault();
                return filteredCommands.value[slashIndex.value];
            }
        } else if (event.key === "Escape") {
            event.preventDefault();
            closeSlash();
        }
        return null;
    }

    /**
     * Replace the `/query` substring (from `slashStart` to current
     * caret) with the command's `insert` template. Returns the new
     * content + caret offset so the caller can update v-model and
     * restore the caret on next tick.
     */
    function applyCommand(textarea, command, content) {
        const start = slashStart.value;
        const caret = textarea.selectionStart;
        const before = content.slice(0, start);
        const after = content.slice(caret);
        const newContent = before + command.insert + after;
        const newCaret =
            command.cursorOffset !== undefined
                ? start + command.cursorOffset
                : start + command.insert.length;

        closeSlash();
        return { newContent, newCaret };
    }

    function closeSlash() {
        showSlash.value = false;
        slashQuery.value = "";
        slashIndex.value = 0;
        slashStart.value = null;
    }

    return {
        showSlash,
        slashIndex,
        slashPosition,
        filteredCommands,
        onInput,
        onKeydown,
        applyCommand,
        closeSlash,
    };
}
