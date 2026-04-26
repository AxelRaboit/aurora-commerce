import { ref, watch } from "vue";

const STORAGE_KEY = "velox-theme";

function getInitial() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === "dark" || stored === "light") return stored;
    return window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light";
}

function apply(newTheme) {
    const htmlElement = document.documentElement;
    htmlElement.classList.add("theme-transitioning");
    htmlElement.classList.toggle("dark", newTheme === "dark");
    window.setTimeout(
        () => htmlElement.classList.remove("theme-transitioning"),
        300,
    );
}

const theme = ref(getInitial());
apply(theme.value);

export function useTheme() {
    watch(theme, (newTheme) => {
        apply(newTheme);
        localStorage.setItem(STORAGE_KEY, newTheme);
    });

    function toggle() {
        theme.value = theme.value === "dark" ? "light" : "dark";
    }

    return { theme, toggle };
}
