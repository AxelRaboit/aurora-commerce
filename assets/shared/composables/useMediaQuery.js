import { onBeforeUnmount, ref } from "vue";

/**
 * Reactive boolean ref tracking a CSS media query. SSR / non-browser
 * environments (jsdom without matchMedia) fall back to `false` and
 * never update.
 *
 * @param {string} query - any valid media query string, e.g.
 *   "(max-width: 767px)" or "(prefers-color-scheme: dark)".
 * @returns {{ matches: import('vue').Ref<boolean> }}
 */
export function useMediaQuery(query) {
    const matches = ref(false);

    if (
        typeof window === "undefined" ||
        typeof window.matchMedia !== "function"
    ) {
        return { matches };
    }

    const mql = window.matchMedia(query);
    matches.value = mql.matches;

    const onChange = (event) => {
        matches.value = event.matches;
    };

    if (typeof mql.addEventListener === "function") {
        mql.addEventListener("change", onChange);
        onBeforeUnmount(() => mql.removeEventListener("change", onChange));
    } else if (typeof mql.addListener === "function") {
        mql.addListener(onChange);
        onBeforeUnmount(() => mql.removeListener(onChange));
    }

    return { matches };
}
