import { onBeforeUnmount, onMounted } from "vue";

/**
 * Bind a global keyboard shortcut while the component is mounted.
 *
 * Example:
 *   useKeyboardShortcut({ key: "s", ctrl: true }, () => save());
 *   useKeyboardShortcut({ key: "k", ctrl: true }, () => openPalette());
 *
 * `ctrl: true` matches both Control (Win/Linux) and Cmd (macOS).
 * The handler is called with the original event; preventDefault() is called
 * automatically before invocation.
 */
export function useKeyboardShortcut(
    { key, ctrl = false, target = null } = {},
    handler,
) {
    function onKeydown(event) {
        if (event.key.toLowerCase() !== key.toLowerCase()) return;
        if (ctrl && !(event.ctrlKey || event.metaKey)) return;
        if (!ctrl && (event.ctrlKey || event.metaKey)) return;
        event.preventDefault();
        handler(event);
    }

    onMounted(() => (target ?? window).addEventListener("keydown", onKeydown));
    onBeforeUnmount(() =>
        (target ?? window).removeEventListener("keydown", onKeydown),
    );
}
