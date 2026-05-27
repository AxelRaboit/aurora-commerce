import { ref, reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * @typedef {Object} ExtraField
 * @property {*} default - Initial/reset value.
 * @property {(media: object) => *} fromEntity - Reads field value from existing media on openEditMedia.
 */

export function useMediaEdit(props, media) {
    const { t } = useI18n();
    const window = globalThis;
    const extraFields = props.extraFields ?? {};

    const editingMedia = ref(null);
    const editTab = ref("edit");
    const mediaVersions = ref([]);
    const mediaUsage = ref(null);
    const versionsLoading = ref(false);
    const editForm = reactive({
        alt: "",
        caption: "",
        focalX: null,
        focalY: null,
        folderId: null,
        ...Object.fromEntries(
            Object.entries(extraFields).map(([key, def]) => [key, def.default]),
        ),
    });
    const editErrors = ref({});
    const editSaving = ref(false);

    const previewMedia = ref(null);
    const cropMedia = ref(null);
    const qrMedia = ref(null);

    const { request: versionsRequest } = useRequest();
    const { request: usageRequest } = useRequest();
    const { request: editRequest } = useRequest();

    function openEditMedia(item) {
        editingMedia.value = item;
        editTab.value = "edit";
        editErrors.value = {};
        mediaVersions.value = [];
        mediaUsage.value = null;
        Object.assign(editForm, {
            alt: item.alt ?? "",
            caption: item.caption ?? "",
            focalX: item.focalX,
            focalY: item.focalY,
            folderId: item.folderId,
            ...Object.fromEntries(
                Object.entries(extraFields).map(([key, def]) => [
                    key,
                    def.fromEntity
                        ? def.fromEntity(item)
                        : (item[key] ?? def.default),
                ]),
            ),
        });
        loadVersions();
    }

    function closeEditMedia() {
        editingMedia.value = null;
    }

    async function loadVersions() {
        if (!editingMedia.value || versionsLoading.value) return;
        versionsLoading.value = true;
        try {
            const data = await versionsRequest(
                `/backend/media/media/${editingMedia.value.id}/versions`,
                null,
                { method: HttpMethod.Get, noGuard: true },
            );
            mediaVersions.value = data?.versions ?? [];
        } finally {
            versionsLoading.value = false;
        }
    }

    async function loadUsage() {
        if (!editingMedia.value) return;
        const data = await usageRequest(
            `/backend/media/media/${editingMedia.value.id}/usage`,
            null,
            { method: HttpMethod.Get, noGuard: true },
        );
        if (data) mediaUsage.value = data;
    }

    async function submitMediaEdit() {
        if (!editingMedia.value) return;
        editSaving.value = true;
        editErrors.value = {};
        try {
            const url = buildPath(props.updatePath, {
                id: editingMedia.value.id,
            });
            const data = await editRequest(url, editForm, { noGuard: true });
            if (!data) return;
            if (!data.success) {
                editErrors.value = data.errors ?? {};
                return;
            }
            const index = media.value.findIndex((m) => m.id === data.media.id);
            if (index !== -1) media.value[index] = data.media;
            toast.success(t("shared.common.saved"));
            closeEditMedia();
        } finally {
            editSaving.value = false;
        }
    }

    function onFocalPointClick(event) {
        const rect = event.currentTarget.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width;
        const y = (event.clientY - rect.top) / rect.height;
        editForm.focalX = Math.round(Math.max(0, Math.min(1, x)) * 1000) / 1000;
        editForm.focalY = Math.round(Math.max(0, Math.min(1, y)) * 1000) / 1000;
    }

    function resetFocalPoint() {
        editForm.focalX = null;
        editForm.focalY = null;
    }

    function openCrop(item) {
        cropMedia.value = item;
    }

    function onCropped(updatedMedia) {
        const idx = media.value.findIndex((m) => m.id === updatedMedia.id);
        if (idx !== -1) media.value[idx] = updatedMedia;
        if (editingMedia.value?.id === updatedMedia.id) {
            editingMedia.value = updatedMedia;
            // A crop adds a new version — refresh the inline history.
            loadVersions();
        }
    }

    function mediaPermalink(item) {
        return item.permalink ?? window.location.origin + item.url;
    }
    function openQr(item) {
        qrMedia.value = item;
    }
    async function copyUrl(item) {
        try {
            await navigator.clipboard.writeText(mediaPermalink(item));
            toast.success(t("backend.media.url_copied"));
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    return {
        editingMedia,
        editTab,
        mediaVersions,
        mediaUsage,
        versionsLoading,
        editForm,
        editErrors,
        editSaving,
        openEditMedia,
        closeEditMedia,
        loadVersions,
        loadUsage,
        submitMediaEdit,
        onFocalPointClick,
        resetFocalPoint,
        previewMedia,
        cropMedia,
        openCrop,
        onCropped,
        qrMedia,
        openQr,
        copyUrl,
        mediaPermalink,
    };
}
