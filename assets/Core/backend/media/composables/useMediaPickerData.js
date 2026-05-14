import { ref, computed, watch, nextTick, isRef } from "vue";
import {
    buildFolderTree,
    flattenFolders,
} from "@core/backend/media/utils/folderTree.js";
import { MediaTypeFilter } from "@core/utils/enums/media/mediaTypeFilter.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useI18n } from "vue-i18n";
import { Files, Image as ImageIcon, Film, FileText } from "lucide-vue-next";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

export function useMediaPickerData({
    listPath,
    show,
    imagesOnly,
    searchInputRef,
    selected,
}) {
    const { t } = useI18n();
    const { request } = useRequest();

    const items = ref([]);
    const folders = ref([]);
    const loading = ref(false);
    const search = ref("");
    const currentFolderId = ref(null);
    const allMediaView = ref(true);
    const typeFilter = ref(MediaTypeFilter.All);
    let abortCtrl = null;

    const FILTERS = computed(() => {
        if (imagesOnly)
            return [
                {
                    key: MediaTypeFilter.All,
                    label: t("shared.media.filters.all"),
                    icon: Files,
                },
            ];
        return [
            {
                key: MediaTypeFilter.All,
                label: t("shared.media.filters.all"),
                icon: Files,
            },
            {
                key: MediaTypeFilter.Image,
                label: t("shared.media.filters.image"),
                icon: ImageIcon,
            },
            {
                key: MediaTypeFilter.Video,
                label: t("shared.media.filters.video"),
                icon: Film,
            },
            {
                key: MediaTypeFilter.Document,
                label: t("shared.media.filters.document"),
                icon: FileText,
            },
        ];
    });

    const folderTree = computed(() => buildFolderTree(folders.value));
    const collapsed = ref(new Set());
    const flatFolders = computed(() =>
        flattenFolders(folderTree.value, 0, collapsed.value),
    );

    const visibleItems = computed(() =>
        items.value.filter((m) => {
            if (imagesOnly && !m.isImage) return false;
            if (typeFilter.value === MediaTypeFilter.Image)
                return m.mimeType?.startsWith("image/");
            if (typeFilter.value === MediaTypeFilter.Video)
                return m.mimeType?.startsWith("video/");
            if (typeFilter.value === MediaTypeFilter.Document)
                return (
                    !m.mimeType?.startsWith("image/") &&
                    !m.mimeType?.startsWith("video/")
                );
            return true;
        }),
    );

    const childFolders = computed(() =>
        folders.value
            .filter(
                (f) => (f.parentId ?? null) === (currentFolderId.value ?? null),
            )
            .sort((a, b) => a.name.localeCompare(b.name)),
    );

    const currentFolderName = computed(() => {
        if (!currentFolderId.value) return null;
        return (
            folders.value.find((f) => f.id === currentFolderId.value)?.name ??
            null
        );
    });

    async function load() {
        abortCtrl?.abort();
        abortCtrl = new AbortController();
        loading.value = true;
        try {
            const url = new URL(listPath, window.location.origin);
            if (search.value.trim())
                url.searchParams.set("search", search.value.trim());
            if (currentFolderId.value)
                url.searchParams.set("folderId", String(currentFolderId.value));
            else if (allMediaView.value) url.searchParams.set("all", "1");
            const data = await request(url.toString(), null, {
                method: HttpMethod.Get,
                signal: abortCtrl.signal,
                noGuard: true,
            });
            if (!data) return;
            items.value = data.items ?? [];
            folders.value = data.folders ?? folders.value;
            if (
                selected.value &&
                !items.value.some((m) => m.id === selected.value.id)
            )
                selected.value = null;
        } finally {
            loading.value = false;
        }
    }

    watch(currentFolderId, load);

    watch(
        isRef(show) ? show : () => show,
        async (visible) => {
            if (visible) {
                await load();
                await nextTick();
                searchInputRef.value?.focus();
            } else {
                selected.value = null;
                search.value = "";
                typeFilter.value = MediaTypeFilter.All;
            }
        },
        { immediate: true },
    );

    function selectFolder(id) {
        allMediaView.value = false;
        currentFolderId.value = id;
    }
    function selectAllMedia() {
        allMediaView.value = true;
        currentFolderId.value = null;
        load();
    }
    function selectRoot() {
        allMediaView.value = false;
        currentFolderId.value = null;
        load();
    }
    function toggleCollapse(id) {
        const next = new Set(collapsed.value);
        if (next.has(id)) next.delete(id);
        else next.add(id);
        collapsed.value = next;
    }

    return {
        items,
        folders,
        loading,
        search,
        currentFolderId,
        allMediaView,
        typeFilter,
        FILTERS,
        folderTree,
        collapsed,
        flatFolders,
        visibleItems,
        childFolders,
        currentFolderName,
        load,
        selectFolder,
        selectAllMedia,
        selectRoot,
        toggleCollapse,
    };
}
