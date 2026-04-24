import { ref, unref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

/**
 * Manages optimistic-locking state for an edited post: tracks the version
 * loaded from the server, a snapshot of translations at load time (the merge
 * "base"), and the fetching of the remote version for preview or 3-way merge.
 *
 * Returns reactive state and action handlers for the conflict banner / merge
 * overlay wired in PostEditor.vue.
 *
 * @param {{ showPath: import("vue").MaybeRef<string>, postId: import("vue").MaybeRef<number|null> }} config
 */
export function useConflictResolution({ showPath, postId }) {
    const { t } = useI18n();

    const version = ref(null);
    const baseTranslations = ref({});

    const remotePost = ref(null);
    const remoteLoading = ref(false);
    const showMerge = ref(false);
    const mergeRemoteTranslations = ref(null);

    function resolvePath() {
        const id = unref(postId);
        if (id === null || id === undefined) return null;
        return unref(showPath).replace("__id__", id);
    }

    function snapshotBase(translations) {
        baseTranslations.value = JSON.parse(JSON.stringify(translations));
    }

    async function fetchRemotePost() {
        const url = resolvePath();
        if (!url) return null;
        remoteLoading.value = true;
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (data.success) return data.post;
        } catch {
            toast.error(t("common.error"));
        } finally {
            remoteLoading.value = false;
        }
        return null;
    }

    async function openRemoteVersion() {
        const post = await fetchRemotePost();
        if (post) remotePost.value = post;
    }

    async function openMerge() {
        const post = await fetchRemotePost();
        if (!post) return;
        mergeRemoteTranslations.value = post.translations ?? {};
        showMerge.value = true;
    }

    function closeRemoteVersion() {
        remotePost.value = null;
    }

    function closeMerge() {
        showMerge.value = false;
    }

    return {
        version,
        baseTranslations,
        remotePost,
        remoteLoading,
        showMerge,
        mergeRemoteTranslations,
        snapshotBase,
        openRemoteVersion,
        closeRemoteVersion,
        openMerge,
        closeMerge,
    };
}
