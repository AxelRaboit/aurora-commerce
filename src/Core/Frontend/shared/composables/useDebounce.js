import { onUnmounted } from "vue";

/**
 * Returns a debounced version of the given function.
 * The timer is automatically cleared on component unmount.
 */
export function useDebounce(callback, delay = 300) {
    let timer = null;

    onUnmounted(() => clearTimeout(timer));

    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => callback(...args), delay);
    };
}
