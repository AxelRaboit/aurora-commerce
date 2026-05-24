import { onBeforeUnmount, onMounted, watch } from "vue";

/**
 * Attach an IntersectionObserver to a sentinel ref and fire the
 * callback whenever it enters the viewport. Lets a paginated list
 * load its next page as the user scrolls instead of clicking
 * "Load more".
 *
 * The observer is recreated whenever the sentinel ref changes
 * (covers v-if mounts / unmounts).
 *
 * @param {import("vue").Ref<HTMLElement|null>} sentinelRef
 * @param {() => void | Promise<void>} onIntersect
 * @param {object} [opts]
 * @param {string} [opts.rootMargin="200px"] - Trigger before the sentinel is fully visible.
 * @param {() => boolean} [opts.enabled]     - Reactive guard. When it returns false, the
 *                                             observer is detached. Useful to stop loading
 *                                             when hasMore is false.
 */
export function useInfiniteScroll(sentinelRef, onIntersect, opts = {}) {
    const rootMargin = opts.rootMargin ?? "200px";
    const enabled = opts.enabled ?? (() => true);
    let observer = null;

    function attach(node) {
        detach();
        if (!node || !enabled()) return;
        observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting && enabled()) {
                        onIntersect();
                    }
                }
            },
            { rootMargin },
        );
        observer.observe(node);
    }

    function detach() {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
    }

    onMounted(() => attach(sentinelRef.value));
    onBeforeUnmount(() => detach());
    watch(sentinelRef, (node) => attach(node));
}
