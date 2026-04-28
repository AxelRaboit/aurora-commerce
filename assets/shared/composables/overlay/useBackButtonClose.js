import { onBeforeUnmount, onMounted, ref, unref, watch } from "vue";

/**
 * Wires the browser Back button to close an overlay/modal.
 *
 * On open: pushes a history entry. On Back: popstate fires and we call onClose.
 * Programmatic close (X, Escape, backdrop) goes through `requestClose()` which
 * triggers history.back() so the pushed entry is always cleaned up.
 *
 * Stacked overlays work naturally — each instance pushes its own entry and the
 * browser pops them LIFO.
 *
 * @param {object} options
 * @param {import('vue').Ref<boolean> | (() => boolean)} options.isOpen
 * @param {() => void} options.onClose
 */
export function useBackButtonClose({ isOpen, onClose }) {
    const pushed = ref(false);
    const read = () =>
        typeof isOpen === "function" ? isOpen() : unref(isOpen);

    function onPopState() {
        if (!pushed.value) return;
        pushed.value = false;
        onClose();
    }

    watch(read, (open) => {
        if (open && !pushed.value) {
            history.pushState({ __overlayBack: true }, "");
            pushed.value = true;
        } else if (!open && pushed.value) {
            pushed.value = false;
            history.back();
        }
    });

    onMounted(() => window.addEventListener("popstate", onPopState));
    onBeforeUnmount(() => {
        window.removeEventListener("popstate", onPopState);
        if (pushed.value) {
            pushed.value = false;
            history.back();
        }
    });

    function requestClose() {
        if (pushed.value) {
            history.back();
        } else {
            onClose();
        }
    }

    return { requestClose };
}
