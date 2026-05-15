/**
 * Global app loader.
 *
 * Hides #aurora-loader once BOTH conditions are met:
 *   1. The first Vue component under <main> has mounted (page content ready).
 *   2. All layout components have dispatched AppEvents.LayoutMounted.
 *
 * Layout components signal readiness via useLayoutMount(), which dispatches
 * LayoutMounted in onMounted — guaranteed after this module has initialized.
 * The window.load fallback handles error/timeout cases.
 *
 * Markup: templates/Shared/components/app_loader.html.twig
 * Styles: assets/css/components/loader.css
 */

import { AppEvents } from "@/shared/utils/enums/appEvents.js";

// --- Constants ---

const LOADER_ID = "aurora-loader";
const FADE_OUT_MS = 320;
const FALLBACK_AFTER_LOAD_MS = 2500;

// One layout component expected (AppSidebar via useLayoutMount).
// Increment when adding other shell components that call useLayoutMount().
const LAYOUT_COMPONENT_COUNT = 1;

// --- Core ---

function createLoader(loaderEl) {
    const main = document.querySelector("#main-content main");
    let done = false;
    let mainReady = false;
    let layoutMountedCount = 0;

    const hide = () => {
        if (done) return;
        done = true;
        document.removeEventListener("vue:mount", onVueMount, true);
        document.removeEventListener(AppEvents.LayoutMounted, onLayoutMounted);
        loaderEl.classList.add("is-done");
        setTimeout(() => loaderEl.remove(), FADE_OUT_MS);
    };

    const tryHide = () => {
        if (mainReady && layoutMountedCount >= LAYOUT_COMPONENT_COUNT) {
            hide();
        }
    };

    const onLayoutMounted = () => {
        layoutMountedCount++;
        tryHide();
    };

    const onVueMount = (event) => {
        if (!event.target || !main || !main.contains(event.target)) return;
        document.removeEventListener("vue:mount", onVueMount, true);
        mainReady = true;
        tryHide();
    };

    return { hide, onLayoutMounted, onVueMount };
}

// --- Init ---

function initLoader() {
    const loaderEl = document.getElementById(LOADER_ID);
    if (!loaderEl) return;

    const { hide, onLayoutMounted, onVueMount } = createLoader(loaderEl);

    document.addEventListener(AppEvents.LayoutMounted, onLayoutMounted);
    document.addEventListener("vue:mount", onVueMount, true);
    window.addEventListener("load", () =>
        setTimeout(hide, FALLBACK_AFTER_LOAD_MS),
    );
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initLoader, { once: true });
} else {
    initLoader();
}
