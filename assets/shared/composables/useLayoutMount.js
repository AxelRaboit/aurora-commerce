import { onMounted } from "vue";
import { AppEvents } from "@/shared/utils/enums/appEvents.js";

/**
 * Registers a layout barrier in the global app loader.
 *
 * Call this in any component that is part of the page shell (sidemenu, topbar,
 * etc.) and loads independently of the main content. The loader will wait for
 * all registered components to be mounted before hiding.
 *
 * The signal is dispatched in onMounted (not setup) to guarantee it fires
 * after initLoader() has registered its listener.
 */
export function useLayoutMount() {
    onMounted(() => {
        document.dispatchEvent(new CustomEvent(AppEvents.LayoutMounted));
    });
}
