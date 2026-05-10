import { ref } from "vue";
import { useRequest } from "@/shared/composables/http/useRequest.js";

/**
 * Delete-and-redirect flow for detail pages.
 * Triggers POST on `deletePath`; on success, navigates to `redirectPath`.
 */
export function useDetailDelete(deletePath, redirectPath) {
    const showDelete = ref(false);
    const { loading, request } = useRequest();

    async function submit() {
        const data = await request(deletePath, {});
        if (data?.success) window.location.href = redirectPath;
    }

    return { showDelete, loading, submit };
}
