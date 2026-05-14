/**
 * Global app loader.
 *
 * Side-effect module that wires the boot loader (#aurora-loader) to hide
 * itself when the first Vue component mounts under <main>. Imported from
 * app.js *before* Vue controllers are registered so the listener is in place
 * before any vue:mount event can fire.
 *
 * Markup: templates/Shared/components/app_loader.html.twig
 * Styles: assets/css/components/loader.css
 */

const LOADER_ID = "aurora-loader";
const FADE_OUT_MS = 320;
const FALLBACK_AFTER_LOAD_MS = 2500;

function initLoader() {
    const loader = document.getElementById(LOADER_ID);
    if (!loader) {
        return;
    }

    const main = document.querySelector("#main-content main");
    let done = false;

    const hide = () => {
        if (done) {
            return;
        }
        done = true;
        document.removeEventListener("vue:mount", onMount, true);
        loader.classList.add("is-done");
        setTimeout(() => loader.remove(), FADE_OUT_MS);
    };

    const onMount = (event) => {
        if (event.target && main && main.contains(event.target)) {
            hide();
        }
    };

    document.addEventListener("vue:mount", onMount, true);
    window.addEventListener("load", () =>
        setTimeout(hide, FALLBACK_AFTER_LOAD_MS),
    );
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initLoader, { once: true });
} else {
    initLoader();
}
