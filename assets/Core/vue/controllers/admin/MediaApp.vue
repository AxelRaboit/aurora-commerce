<script setup>
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { ref, reactive, computed, nextTick, onMounted, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { Pencil, Trash2, Plus, Folder, Upload, Image as ImageIcon, ChevronRight, ChevronDown, Home, Copy, QrCode, LayoutGrid, List, SortAsc, SortDesc, CheckSquare, Square, X, Move, HardDrive, Eye, Save, Star, Crop } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppImage from "@/shared/components/display/AppImage.vue";
import { useFileSize } from "@/shared/composables/format/useFileSize.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { buildFolderTree, flattenFolders } from "@core/admin/media/utils/folderTree.js";
import { useMultiSelection } from "@/shared/composables/list/useMultiSelection.js";
import MediaQrModal from "@core/admin/media/MediaQrModal.vue";
import MediaCropperModal from "@core/admin/media/MediaCropperModal.vue";

const { t } = useI18n();
const { formatSize } = useFileSize();
const { formatDateTime } = useDateFormat();

const props = defineProps({
    folders: { type: Array, default: () => [] },
    media: { type: Array, default: () => [] },
    currentFolderId: { type: Number, default: null },
    search: { type: String, default: "" },
    uploadPath: { type: String, default: "/admin/media/upload" },
    editPath: { type: String, default: "/admin/media/__id__/edit" },
    deletePath: { type: String, default: "/admin/media/__id__/delete" },
    movePath: { type: String, default: "/admin/media/__id__/move" },
    folderCreatePath: { type: String, default: "/admin/media/folders" },
    folderEditPath: { type: String, default: "/admin/media/folders/__id__/edit" },
    folderDeletePath: { type: String, default: "/admin/media/folders/__id__/delete" },
    reorderPath: { type: String, default: "/admin/media/reorder" },
    bulkDeletePath: { type: String, default: "/admin/media/bulk-delete" },
    listPath: { type: String, default: "/admin/media/list" },
    bulkMovePath: { type: String, default: "/admin/media/bulk-move" },
    cropPath: { type: String, default: "/admin/media/__id__/crop" },
    totalStorageBytes: { type: Number, default: 0 },
});

const folders = ref([...props.folders]);
const media = ref([...props.media]);
const currentFolderId = ref(props.currentFolderId);
const searchQuery = ref(props.search ?? "");

const mediaLoading = ref(false);
let navAbort = null;

function mediaUrl(base, folderId, search) {
    const url = new URL(base, window.location.origin);
    if (folderId) url.searchParams.set("folderId", String(folderId));
    if (search) url.searchParams.set("search", search);
    return url;
}

async function loadMedia(folderId, search) {
    navAbort?.abort();
    navAbort = new AbortController();
    mediaLoading.value = true;
    try {
        const res = await fetch(mediaUrl(props.listPath, folderId, search), { signal: navAbort.signal });
        const data = await res.json();
        media.value = data.items ?? [];
        folders.value = data.folders ?? folders.value;
        currentFolderId.value = folderId ?? null;
        searchQuery.value = search;
        clearSelection();
    } catch (e) {
        if (e.name !== "AbortError") toast.error(t("shared.common.error"));
    } finally {
        mediaLoading.value = false;
    }
}

async function navigateTo(folderId, search = searchQuery.value) {
    await loadMedia(folderId, search);
    history.pushState({ folderId, search }, "", mediaUrl("/admin/media", folderId, search));
}

async function onPopState(event) {
    await loadMedia(event.state?.folderId ?? null, event.state?.search ?? "");
}

onMounted(() => {
    history.replaceState(
        { folderId: currentFolderId.value, search: searchQuery.value },
        "",
        mediaUrl("/admin/media", currentFolderId.value, searchQuery.value),
    );
    window.addEventListener("popstate", onPopState);
});

onUnmounted(() => {
    window.removeEventListener("popstate", onPopState);
});

const folderTree = computed(() => buildFolderTree(folders.value));

// Folders collapsed by the user (the ones whose children are hidden in the tree).
const collapsedFolderIds = ref(loadCollapsedFolderIds());

// ── Favourite folders ─────────────────────────────────────────────────────────
const favouriteFolderIds = ref(loadFavouriteFolderIds());

function loadFavouriteFolderIds() {
    try {
        const raw = localStorage.getItem("aurora-media-favourite-folders");
        return raw ? new Set(JSON.parse(raw)) : new Set();
    } catch { return new Set(); }
}

function toggleFavourite(folderId) {
    const s = new Set(favouriteFolderIds.value);
    if (s.has(folderId)) s.delete(folderId); else s.add(folderId);
    favouriteFolderIds.value = s;
    try { localStorage.setItem("aurora-media-favourite-folders", JSON.stringify([...s])); } catch {}
}

const favouriteFolders = computed(() =>
    folders.value.filter((f) => favouriteFolderIds.value.has(f.id))
);

function loadCollapsedFolderIds() {
    try {
        const raw = localStorage.getItem("aurora-media-collapsed-folders");
        if (!raw) return new Set();
        return new Set(JSON.parse(raw));
    } catch {
        return new Set();
    }
}

function persistCollapsedFolderIds() {
    try {
        localStorage.setItem("aurora-media-collapsed-folders", JSON.stringify([...collapsedFolderIds.value]));
    } catch {}
}

function toggleCollapse(folderId) {
    if (collapsedFolderIds.value.has(folderId)) {
        collapsedFolderIds.value.delete(folderId);
    } else {
        collapsedFolderIds.value.add(folderId);
    }
    // Trigger reactivity: Sets aren't deeply reactive in Vue, so clone
    collapsedFolderIds.value = new Set(collapsedFolderIds.value);
    persistCollapsedFolderIds();
}

const flatFolders = computed(() => flattenFolders(folderTree.value, 0, collapsedFolderIds.value));

// flatFolders for selects (modals) always expanded, no depth-skipping
const allFlatFolders = computed(() => flattenFolders(folderTree.value));

const currentFolder = computed(() => folders.value.find((f) => f.id === currentFolderId.value) ?? null);

const breadcrumbs = computed(() => {
    const chain = [];
    let current = currentFolder.value;
    while (current) {
        chain.unshift(current);
        current = folders.value.find((f) => f.id === current.parentId) ?? null;
    }
    return chain;
});

// ── View preferences ─────────────────────────────────────────────────────────
const viewMode = ref(localStorage.getItem("aurora-media-view") ?? "grid");
function setViewMode(mode) { viewMode.value = mode; localStorage.setItem("aurora-media-view", mode); }

// ── Type filter ───────────────────────────────────────────────────────────────
const typeFilter = ref("all");
const TYPE_FILTERS = [
    { key: "all", label: "admin.media.filterAll" },
    { key: "image", label: "admin.media.filterImages" },
    { key: "video", label: "admin.media.filterVideos" },
    { key: "application/pdf", label: "admin.media.filterPdf" },
    { key: "other", label: "admin.media.filterOther" },
];

// ── Sort ─────────────────────────────────────────────────────────────────────
const sortBy = ref(localStorage.getItem("aurora-media-sort") ?? "position");
const sortDir = ref(localStorage.getItem("aurora-media-sort-dir") ?? "asc");
function setSort(field) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === "asc" ? "desc" : "asc";
    } else {
        sortBy.value = field;
        sortDir.value = "asc";
    }
    localStorage.setItem("aurora-media-sort", sortBy.value);
    localStorage.setItem("aurora-media-sort-dir", sortDir.value);
}

// ── Filtered + sorted media ───────────────────────────────────────────────────
const displayedMedia = computed(() => {
    let list = [...media.value];
    if (typeFilter.value !== "all") {
        list = list.filter((m) => {
            if (typeFilter.value === "image") return m.mimeType.startsWith("image/");
            if (typeFilter.value === "video") return m.mimeType.startsWith("video/");
            if (typeFilter.value === "application/pdf") return m.mimeType === "application/pdf";
            return !m.mimeType.startsWith("image/") && !m.mimeType.startsWith("video/") && m.mimeType !== "application/pdf";
        });
    }
    const dir = sortDir.value === "asc" ? 1 : -1;
    list.sort((a, b) => {
        if (sortBy.value === "name") return dir * a.originalName.localeCompare(b.originalName);
        if (sortBy.value === "size") return dir * (a.size - b.size);
        if (sortBy.value === "date") return dir * (new Date(a.createdAt ?? 0) - new Date(b.createdAt ?? 0));
        return dir * (a.position - b.position);
    });
    return list;
});

// ── Multi-selection ───────────────────────────────────────────────────────────
const { selectedIds, isSelecting, toggle: toggleSelect, clear: clearSelection } = useMultiSelection();
function selectAll() { selectedIds.value = new Set(displayedMedia.value.map((m) => m.id)); }

const pendingBulkDelete = ref(false);
const bulkDeleteLoading = ref(false);

async function doBulkDelete() {
    if (!selectedIds.value.size) return;
    bulkDeleteLoading.value = true;
    try {
        const res = await fetch(props.bulkDeletePath, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ ids: [...selectedIds.value] }),
        });
        if (!(await res.json()).success) throw new Error();
        media.value = media.value.filter((m) => !selectedIds.value.has(m.id));
        clearSelection();
        pendingBulkDelete.value = false;
        toast.success(t("admin.media.bulkDeleted"));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        bulkDeleteLoading.value = false;
    }
}

const bulkMoveTargetId = ref(null);
const openBulkMove = ref(false);
const window = globalThis;
async function bulkMove() {
    if (!selectedIds.value.size) return;
    try {
        const res = await fetch(props.bulkMovePath, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ ids: [...selectedIds.value], folderId: bulkMoveTargetId.value }),
        });
        if (!(await res.json()).success) throw new Error();
        if (bulkMoveTargetId.value !== currentFolderId.value) {
            media.value = media.value.filter((m) => !selectedIds.value.has(m.id));
        }
        clearSelection();
        bulkMoveTargetId.value = null;
        toast.success(t("admin.media.bulkMoved"));
    } catch { toast.error(t("shared.common.error")); }
}

// ── Preview ───────────────────────────────────────────────────────────────────
const previewMedia = ref(null);

// ── Crop ─────────────────────────────────────────────────────────────────────
const cropMedia = ref(null);

function openCrop(item) {
    cropMedia.value = item;
}

function onCropped(updatedMedia) {
    const idx = media.value.findIndex((m) => m.id === updatedMedia.id);
    if (idx !== -1) media.value[idx] = updatedMedia;
    if (editingMedia.value?.id === updatedMedia.id) editingMedia.value = updatedMedia;
}

const historyActionLabel = (action) => t(`admin.media.historyAction.${action}`);

// ── QR Code ───────────────────────────────────────────────────────────────────
const qrMedia = ref(null);
function mediaPermalink(item) {
    return item.permalink ?? (window.location.origin + item.url);
}

function openQr(item) {
    qrMedia.value = item;
}

// ── Copy URL ─────────────────────────────────────────────────────────────────
async function copyUrl(item) {
    try {
        await navigator.clipboard.writeText(mediaPermalink(item));
        toast.success(t("admin.media.urlCopied"));
    } catch { toast.error(t("shared.common.error")); }
}

// ── Upload ───────────────────────────────────────────────────────────────────
const uploadInput = ref(null);
const uploading = ref(false);
const uploadProgress = ref([]); // [{name, percent}]
const filesDragOver = ref(false);

function uploadWithProgress(url, formData, onProgress) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.upload.onprogress = (e) => { if (e.lengthComputable) onProgress(Math.round((e.loaded / e.total) * 100)); };
        xhr.onload = () => { try { resolve(JSON.parse(xhr.responseText)); } catch { reject(); } };
        xhr.onerror = reject;
        xhr.open("POST", url);
        xhr.send(formData);
    });
}

async function uploadFileList(files) {
    if (!files.length) return;
    uploading.value = true;
    uploadProgress.value = files.map((f) => ({ name: f.name, percent: 0 }));
    try {
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const body = new FormData();
            body.append("image", file);
            if (currentFolderId.value) body.append("folderId", String(currentFolderId.value));
            const data = await uploadWithProgress(props.uploadPath, body, (p) => { uploadProgress.value[i].percent = p; });
            if (data.media) media.value.unshift(data.media);
        }
        toast.success(t("admin.media.uploaded", { count: files.length }));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        uploading.value = false;
        uploadProgress.value = [];
        if (uploadInput.value) uploadInput.value.value = "";
    }
}

async function uploadFiles(event) {
    await uploadFileList(Array.from(event.target.files ?? []));
}

function onMainDragOver(event) {
    if (event.dataTransfer.types.includes("Files")) {
        event.preventDefault();
        filesDragOver.value = true;
    }
}

function onMainDragLeave(event) {
    if (!event.currentTarget.contains(event.relatedTarget)) {
        filesDragOver.value = false;
    }
}

async function onMainDrop(event) {
    if (!event.dataTransfer.types.includes("Files")) return;
    event.preventDefault();
    filesDragOver.value = false;
    await uploadFileList(Array.from(event.dataTransfer.files));
}

// ── Edit media ───────────────────────────────────────────────────────────────
const editingMedia = ref(null);
const editTab = ref("edit"); // "edit" | "history" | "usage"
const mediaHistory = ref([]);
const mediaUsage = ref(null);
const historyLoading = ref(false);
const editForm = reactive({ alt: "", caption: "", focalX: null, focalY: null, folderId: null });
const editErrors = ref({});
const editSaving = ref(false);

function openEditMedia(item) {
    editingMedia.value = item;
    editTab.value = "edit";
    editErrors.value = {};
    mediaHistory.value = [];
    mediaUsage.value = null;
    Object.assign(editForm, {
        alt: item.alt ?? "",
        caption: item.caption ?? "",
        focalX: item.focalX,
        focalY: item.focalY,
        folderId: item.folderId,
    });
}

async function loadHistory() {
    if (!editingMedia.value || historyLoading.value) return;
    historyLoading.value = true;
    try {
        const res = await fetch(`/admin/media/${editingMedia.value.id}/history`);
        const data = await res.json();
        mediaHistory.value = data.items ?? [];
    } catch { /* ignore */ } finally {
        historyLoading.value = false;
    }
}

async function loadUsage() {
    if (!editingMedia.value) return;
    try {
        const res = await fetch(`/admin/media/${editingMedia.value.id}/usage`);
        mediaUsage.value = await res.json();
    } catch { /* ignore */ }
}

function openHistoryTab() {
    editTab.value = "history";
    if (!mediaHistory.value.length) loadHistory();
}

function closeEditMedia() {
    editingMedia.value = null;
}

async function submitMediaEdit() {
    if (!editingMedia.value) return;
    editSaving.value = true;
    editErrors.value = {};
    try {
        const url = buildPath(props.editPath, { id: editingMedia.value.id });
        const response = await fetch(url, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(editForm),
        });
        const data = await response.json();
        if (!data.success) {
            editErrors.value = data.errors ?? {};
            return;
        }
        const index = media.value.findIndex((m) => m.id === data.media.id);
        if (index !== -1) media.value[index] = data.media;
        toast.success(t("shared.common.saved"));
        closeEditMedia();
    } catch {
        toast.error(t("shared.common.error"));
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

// ── Delete media ─────────────────────────────────────────────────────────────
const deletingMedia = ref(null);

async function confirmDeleteMedia() {
    const item = deletingMedia.value;
    if (!item) return;
    try {
        const response = await fetch(buildPath(props.deletePath, { id: item.id }), { method: HttpMethod.Post });
        const data = await response.json();
        if (!data.success) {
            toast.error(t("shared.common.error"));
            return;
        }
        media.value = media.value.filter((m) => m.id !== item.id);
        toast.success(t("shared.common.deleted"));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        deletingMedia.value = null;
    }
}

// ── Folder modal ─────────────────────────────────────────────────────────────
const folderModal = reactive({ open: false, editing: null, errors: {}, saving: false });
const folderForm = reactive({ name: "", parentId: null });

function openCreateFolder() {
    folderModal.editing = null;
    folderModal.errors = {};
    folderForm.name = "";
    folderForm.parentId = currentFolderId.value;
    folderModal.open = true;
}

function openEditFolder(folder) {
    folderModal.editing = folder;
    folderModal.errors = {};
    folderForm.name = folder.name;
    folderForm.parentId = folder.parentId;
    folderModal.open = true;
}

async function submitFolder() {
    folderModal.saving = true;
    folderModal.errors = {};
    try {
        const url = folderModal.editing
            ? buildPath(props.folderEditPath, { id: folderModal.editing.id })
            : props.folderCreatePath;
        const response = await fetch(url, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(folderForm),
        });
        const data = await response.json();
        if (!data.success) {
            folderModal.errors = data.errors ?? {};
            return;
        }
        if (folderModal.editing) {
            const idx = folders.value.findIndex((f) => f.id === data.folder.id);
            if (idx !== -1) folders.value[idx] = data.folder;
        } else {
            folders.value.push(data.folder);
        }
        toast.success(t("shared.common.saved"));
        folderModal.open = false;
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        folderModal.saving = false;
    }
}

const deletingFolder = ref(null);

async function confirmDeleteFolder() {
    const folder = deletingFolder.value;
    if (!folder) return;
    try {
        const response = await fetch(buildPath(props.folderDeletePath, { id: folder.id }), { method: HttpMethod.Post });
        const data = await response.json();
        if (!data.success) {
            toast.error(t("shared.common.error"));
            return;
        }
        folders.value = folders.value.filter((f) => f.id !== folder.id);
        if (currentFolderId.value === folder.id) {
            navigateTo(folder.parentId ?? null);
            return;
        }
        toast.success(t("shared.common.deleted"));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        deletingFolder.value = null;
    }
}

const folderParentOptions = computed(() => {
    if (!folderModal.editing) return flatFolders.value;
    const forbidden = new Set([folderModal.editing.id]);
    const addDescendants = (id) => {
        for (const f of folders.value) {
            if (f.parentId === id && !forbidden.has(f.id)) {
                forbidden.add(f.id);
                addDescendants(f.id);
            }
        }
    };
    addDescendants(folderModal.editing.id);
    return allFlatFolders.value.filter((f) => !forbidden.has(f.id));
});

function withDepthLabel(folders) {
    return folders.map((f) => ({ ...f, displayLabel: "— ".repeat(f.depth) + f.name }));
}
const folderEditOptions = computed(() => withDepthLabel(allFlatFolders.value));
const folderParentSelectOptions = computed(() => withDepthLabel(folderParentOptions.value));

// ── Drag & drop ──────────────────────────────────────────────────────────────
const dragOverFolderId = ref(null);
const dragOverMediaId = ref(null);
const rootDragOver = ref(false);

function onMediaDragStart(event, mediaItem) {
    event.dataTransfer.effectAllowed = "move";
    event.dataTransfer.setData("application/x-aurora-media", String(mediaItem.id));
}

function onFolderDragStart(event, folder) {
    event.dataTransfer.effectAllowed = "move";
    event.dataTransfer.setData("application/x-aurora-folder", String(folder.id));
}

function onFolderDragOver(event, folderId) {
    if (event.dataTransfer.types.includes("application/x-aurora-media") || event.dataTransfer.types.includes("application/x-aurora-folder")) {
        event.preventDefault();
        dragOverFolderId.value = folderId;
        rootDragOver.value = false;
    }
}

function onRootDragOver(event) {
    if (event.dataTransfer.types.includes("application/x-aurora-media") || event.dataTransfer.types.includes("application/x-aurora-folder")) {
        event.preventDefault();
        rootDragOver.value = true;
        dragOverFolderId.value = null;
    }
}

function onDragLeave() {
    dragOverFolderId.value = null;
    dragOverMediaId.value = null;
    rootDragOver.value = false;
}

// Reorder via drag-drop only makes sense in "position" (#) sort — any other
// sort would re-shuffle the items right after, so we skip the drop target.
const reorderEnabled = computed(() => sortBy.value === "position");

function onMediaItemDragOver(event, mediaItem) {
    if (!event.dataTransfer.types.includes("application/x-aurora-media")) return;
    if (!reorderEnabled.value) return;
    event.preventDefault();
    event.stopPropagation();
    dragOverMediaId.value = mediaItem.id;
    dragOverFolderId.value = null;
}

async function onMediaItemDrop(event, targetItem) {
    if (!reorderEnabled.value) return;
    event.preventDefault();
    event.stopPropagation();
    dragOverMediaId.value = null;
    const draggedId = Number(event.dataTransfer.getData("application/x-aurora-media"));
    if (!draggedId || draggedId === targetItem.id) return;

    const list = [...media.value];
    const fromIdx = list.findIndex((m) => m.id === draggedId);
    const toIdx = list.findIndex((m) => m.id === targetItem.id);
    if (fromIdx === -1 || toIdx === -1) return;

    list.splice(toIdx, 0, list.splice(fromIdx, 1)[0]);
    // Reassign client-side `position` so the `displayedMedia` computed (which sorts
    // by position when sortBy === "position") agrees with the new visual order.
    // The backend will canonicalise positions in reorderMedia.
    list.forEach((item, index) => { item.position = index; });
    media.value = list;

    await reorderMedia(list.map((m) => m.id));
}

async function reorderMedia(ids) {
    try {
        await fetch(props.reorderPath, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ ids }),
        });
    } catch {
        toast.error(t("shared.common.error"));
    }
}

async function onFolderDrop(event, targetFolderId) {
    event.preventDefault();
    const mediaId = event.dataTransfer.getData("application/x-aurora-media");
    const folderId = event.dataTransfer.getData("application/x-aurora-folder");
    dragOverFolderId.value = null;
    rootDragOver.value = false;

    if (mediaId) {
        await moveMedia(Number(mediaId), targetFolderId);
    } else if (folderId) {
        await moveFolder(Number(folderId), targetFolderId);
    }
}

async function moveMedia(mediaId, folderId) {
    try {
        const response = await fetch(buildPath(props.movePath, { id: mediaId }), {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ folderId }),
        });
        const data = await response.json();
        if (!data.success) {
            toast.error(t("shared.common.error"));
            return;
        }
        // If the target is the current folder, keep the item; else remove it from the grid
        if (folderId !== currentFolderId.value) {
            media.value = media.value.filter((m) => m.id !== mediaId);
        } else {
            const idx = media.value.findIndex((m) => m.id === mediaId);
            if (idx !== -1) media.value[idx] = data.media;
        }
        toast.success(t("admin.media.moved"));
    } catch {
        toast.error(t("shared.common.error"));
    }
}

async function moveFolder(folderId, newParentId) {
    if (folderId === newParentId) return;
    const folder = folders.value.find((f) => f.id === folderId);
    if (!folder) return;
    try {
        const response = await fetch(buildPath(props.folderEditPath, { id: folderId }), {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ name: folder.name, parentId: newParentId }),
        });
        const data = await response.json();
        if (!data.success) {
            toast.error(data.errors?.parentId ?? t("shared.common.error"));
            return;
        }
        const idx = folders.value.findIndex((f) => f.id === folderId);
        if (idx !== -1) folders.value[idx] = data.folder;
        toast.success(t("admin.media.moved"));
    } catch {
        toast.error(t("shared.common.error"));
    }
}
</script>

<template>
    <div class="flex flex-col gap-4 min-h-[calc(100vh-8rem)]">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 bg-surface border border-line/60 rounded-xl px-4 py-3">
            <nav class="flex items-center gap-1 text-sm text-muted min-w-0 flex-1 flex-wrap">
                <button type="button" class="flex items-center gap-1.5 hover:text-primary transition-colors shrink-0" v-on:click="navigateTo(null)">
                    <Home class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("admin.media.rootFolder") }}
                </button>
                <template v-for="crumb in breadcrumbs" :key="crumb.id">
                    <ChevronRight class="w-3 h-3 shrink-0" :stroke-width="2" />
                    <button type="button" class="hover:text-primary transition-colors truncate" v-on:click="navigateTo(crumb.id)">{{ crumb.name }}</button>
                </template>
            </nav>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:shrink-0">
                <div class="w-full sm:w-64">
                    <AppSearchInput
                        v-model="searchQuery"
                        :placeholder="t('admin.media.searchPlaceholder')"
                        v-on:search="(q) => navigateTo(currentFolderId, q)"
                    />
                </div>
                <input
                    ref="uploadInput"
                    type="file"
                    accept="image/*"
                    multiple
                    class="hidden"
                    v-on:change="uploadFiles"
                >
                <AppButton
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    :loading="uploading"
                    v-on:click="uploadInput?.click()"
                >
                    <Upload class="w-4 h-4" :stroke-width="2" />
                    {{ t("admin.media.upload") }}
                </AppButton>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-4">
            <aside class="lg:w-72 shrink-0 space-y-2">
                <div v-if="favouriteFolders.length" class="space-y-0.5">
                    <h3 class="text-xs font-semibold text-secondary uppercase tracking-wide flex items-center gap-1.5 px-1">
                        <Star class="w-3 h-3 text-amber-400" :stroke-width="2" fill="currentColor" />
                        {{ t("admin.media.favourites") }}
                    </h3>
                    <button
                        v-for="fav in favouriteFolders"
                        :key="fav.id"
                        type="button"
                        class="w-full text-left px-3 py-1.5 rounded-lg transition-colors flex items-center gap-2 text-sm min-w-0"
                        :class="currentFolderId === fav.id ? 'bg-accent-600/15 text-accent-400' : 'hover:bg-surface-2 text-primary'"
                        v-on:click="navigateTo(fav.id)"
                    >
                        <Folder class="w-3.5 h-3.5 shrink-0" :stroke-width="2" />
                        <span class="truncate flex-1">{{ fav.name }}</span>
                    </button>
                    <div class="border-t border-line/40 my-1" />
                </div>

                <div class="flex items-center gap-1.5">
                    <h2 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("admin.media.folders") }}</h2>
                    <button
                        type="button"
                        class="p-0.5 text-muted hover:text-primary hover:bg-surface-2 rounded transition-colors"
                        :title="t('admin.media.newFolder')"
                        v-on:click="openCreateFolder"
                    >
                        <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                    </button>
                </div>
                <div class="space-y-0.5">
                    <button
                        type="button"
                        class="w-full text-left px-3 py-2 rounded-lg transition-colors flex items-center gap-2 border"
                        :class="[
                            !currentFolderId
                                ? 'bg-accent-600/15 text-accent-400 border-accent-600/30'
                                : 'hover:bg-surface-2 text-primary border-transparent',
                            rootDragOver ? 'ring-2 ring-accent-500' : '',
                        ]"
                        v-on:click="navigateTo(null)"
                        v-on:dragover="onRootDragOver"
                        v-on:dragleave="onDragLeave"
                        v-on:drop="onFolderDrop($event, null)"
                    >
                        <Home class="w-4 h-4 shrink-0" :stroke-width="2" />
                        <span class="flex-1 text-sm font-medium">{{ t("admin.media.rootFolder") }}</span>
                    </button>
                    <div
                        v-for="folder in flatFolders"
                        :key="folder.id"
                        class="group flex items-center gap-1"
                        :style="{ paddingLeft: `${folder.depth * 1}rem` }"
                        draggable="true"
                        v-on:dragstart="onFolderDragStart($event, folder)"
                    >
                        <button
                            v-if="folder.childCount > 0"
                            type="button"
                            class="p-1 -ml-1 text-muted hover:text-primary rounded shrink-0"
                            :title="collapsedFolderIds.has(folder.id) ? t('admin.media.expand') : t('admin.media.collapse')"
                            v-on:click.stop="toggleCollapse(folder.id)"
                        >
                            <ChevronRight v-if="collapsedFolderIds.has(folder.id)" class="w-3 h-3" :stroke-width="2" />
                            <ChevronDown v-else class="w-3 h-3" :stroke-width="2" />
                        </button>
                        <span v-else class="w-4 shrink-0" />
                        <button
                            type="button"
                            class="flex-1 text-left px-3 py-2 rounded-lg transition-colors flex items-center gap-2 border min-w-0"
                            :class="[
                                currentFolderId === folder.id
                                    ? 'bg-accent-600/15 text-accent-400 border-accent-600/30'
                                    : 'hover:bg-surface-2 text-primary border-transparent',
                                dragOverFolderId === folder.id ? 'ring-2 ring-accent-500' : '',
                            ]"
                            v-on:click="navigateTo(folder.id)"
                            v-on:dragover="onFolderDragOver($event, folder.id)"
                            v-on:dragleave="onDragLeave"
                            v-on:drop="onFolderDrop($event, folder.id)"
                        >
                            <Folder class="w-4 h-4 shrink-0" :stroke-width="2" />
                            <span class="flex-1 text-sm truncate">{{ folder.name }}</span>
                            <span v-if="folder.mediaCount > 0" class="text-xs text-muted font-mono shrink-0">{{ folder.mediaCount }}</span>
                        </button>
                        <div class="opacity-0 group-hover:opacity-100 flex gap-0.5 transition-opacity">
                            <button
                                type="button"
                                class="p-1 rounded transition-colors"
                                :class="favouriteFolderIds.has(folder.id) ? 'text-amber-400' : 'text-muted hover:text-amber-400'"
                                :title="favouriteFolderIds.has(folder.id) ? t('admin.media.unfavourite') : t('admin.media.favourite')"
                                v-on:click.stop="toggleFavourite(folder.id)"
                            >
                                <Star class="w-3.5 h-3.5" :stroke-width="2" :fill="favouriteFolderIds.has(folder.id) ? 'currentColor' : 'none'" />
                            </button>
                            <AppIconButton color="accent" v-on:click="openEditFolder(folder)">
                                <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton color="rose" v-on:click="deletingFolder = folder">
                                <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                            </AppIconButton>
                        </div>
                    </div>
                </div>
                <div v-if="totalStorageBytes > 0" class="pt-3 border-t border-line/40 space-y-1.5">
                    <div class="flex items-center justify-between text-xs text-muted">
                        <span class="flex items-center gap-1"><HardDrive class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("admin.media.storage") }}</span>
                        <span>{{ formatSize(totalStorageBytes) }}</span>
                    </div>
                </div>
            </aside>

            <main
                class="flex-1 min-w-0 min-h-[60vh] space-y-3 relative"
                v-on:dragover="onMainDragOver"
                v-on:dragleave="onMainDragLeave"
                v-on:drop="onMainDrop"
            >
                <div v-if="filesDragOver" class="absolute inset-0 z-10 rounded-xl border-2 border-dashed border-accent-400 bg-accent-500/10 flex flex-col items-center justify-center gap-3 pointer-events-none">
                    <Upload class="w-14 h-14 text-accent-400" :stroke-width="1.5" />
                    <span class="text-base font-medium text-accent-400">{{ t("admin.media.dropToUpload") }}</span>
                </div>

                <div v-if="uploadProgress.length" class="space-y-1.5 bg-surface border border-line/60 rounded-xl p-3">
                    <div v-for="(f, i) in uploadProgress" :key="i" class="space-y-1">
                        <div class="flex justify-between text-xs text-muted">
                            <span class="truncate max-w-[80%]">{{ f.name }}</span>
                            <span>{{ f.percent }}%</span>
                        </div>
                        <div class="h-1.5 bg-surface-3 rounded-full overflow-hidden">
                            <div class="h-full bg-accent-500 transition-all duration-200 rounded-full" :style="{ width: f.percent + '%' }" />
                        </div>
                    </div>
                </div>

                <div v-if="selectedIds.size" class="flex flex-wrap items-center gap-2 bg-accent-500/10 border border-accent-400/30 rounded-xl px-4 py-2.5">
                    <span class="text-sm font-medium text-accent-400">{{ selectedIds.size }} {{ t("admin.media.selected") }}</span>
                    <div class="flex gap-2 ml-auto flex-wrap">
                        <AppButton size="sm" variant="ghost" v-on:click="selectAll">{{ t("admin.media.selectAll") }}</AppButton>
                        <AppButton size="sm" variant="ghost" v-on:click="() => { bulkMoveTargetId = null; }" v-on:click.prevent="openBulkMove = true">
                            <Move class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("admin.media.move") }}
                        </AppButton>
                        <AppButton size="sm" variant="danger" v-on:click="pendingBulkDelete = true">
                            <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("shared.common.delete") }}
                        </AppButton>
                        <AppButton size="sm" variant="ghost" v-on:click="clearSelection">
                            <X class="w-3.5 h-3.5" :stroke-width="2" />
                        </AppButton>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex gap-1 flex-wrap">
                        <button
                            v-for="f in TYPE_FILTERS"
                            :key="f.key"
                            type="button"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors"
                            :class="typeFilter === f.key ? 'bg-accent-500/15 text-accent-400' : 'text-muted hover:text-primary hover:bg-surface-2'"
                            v-on:click="typeFilter = f.key"
                        >
                            {{ t(f.label) }}
                        </button>
                    </div>
                    <div class="ml-auto flex items-center gap-1.5">
                        <div class="flex gap-1 border border-line/60 rounded-lg p-0.5">
                            <button
                                v-for="s in [{k:'position',l:'#',title:t('admin.media.sortPositionHint')},{k:'name',l:'A-Z',title:t('admin.media.sortName')},{k:'size',l:'KB',title:t('admin.media.sortSize')},{k:'date',l:t('admin.media.sortDate'),title:t('admin.media.sortDate')}]"
                                :key="s.k"
                                type="button"
                                class="px-2 py-0.5 rounded text-xs transition-colors flex items-center gap-1"
                                :class="sortBy === s.k ? 'bg-surface-3 text-primary' : 'text-muted hover:text-primary'"
                                :title="s.title"
                                v-on:click="setSort(s.k)"
                            >
                                {{ s.l }}
                                <SortAsc v-if="sortBy === s.k && sortDir === 'asc'" class="w-3 h-3" :stroke-width="2" />
                                <SortDesc v-else-if="sortBy === s.k" class="w-3 h-3" :stroke-width="2" />
                            </button>
                        </div>
                        <div class="flex border border-line/60 rounded-lg p-0.5">
                            <button type="button" :class="viewMode === 'grid' ? 'bg-surface-3 text-primary' : 'text-muted hover:text-primary'" class="p-1 rounded transition-colors" v-on:click="setViewMode('grid')">
                                <LayoutGrid class="w-4 h-4" :stroke-width="2" />
                            </button>
                            <button type="button" :class="viewMode === 'list' ? 'bg-surface-3 text-primary' : 'text-muted hover:text-primary'" class="p-1 rounded transition-colors" v-on:click="setViewMode('list')">
                                <List class="w-4 h-4" :stroke-width="2" />
                            </button>
                        </div>
                        <button type="button" class="p-1 rounded-lg border border-line/60 transition-colors" :class="isSelecting ? 'bg-accent-500/15 text-accent-400' : 'text-muted hover:text-primary'" v-on:click="isSelecting = !isSelecting; if (!isSelecting) clearSelection()">
                            <CheckSquare class="w-4 h-4" :stroke-width="2" />
                        </button>
                    </div>
                </div>

                <AppMessage v-if="media.some((m) => !m.alt)" variant="warning">{{ t("admin.media.altWarning") }}</AppMessage>

                <div v-if="mediaLoading" class="flex items-center justify-center py-16 text-muted text-sm">
                    <span class="animate-pulse">{{ t('shared.common.loading') }}</span>
                </div>

                <template v-else>
                    <AppNoData v-if="!displayedMedia.length" :message="t('admin.media.empty')" />

                    <div v-else-if="viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                        <div
                            v-for="item in displayedMedia"
                            :key="item.id"
                            class="group relative bg-surface border rounded-lg overflow-hidden transition-colors cursor-pointer"
                            :class="[
                                dragOverMediaId === item.id ? 'border-accent-400 ring-2 ring-accent-400/50' : 'border-line/60 hover:border-accent-400',
                                selectedIds.has(item.id) ? 'ring-2 ring-accent-500' : '',
                            ]"
                            draggable="true"
                            v-on:click="isSelecting ? toggleSelect(item.id) : openEditMedia(item)"
                            v-on:dragstart="onMediaDragStart($event, item)"
                            v-on:dragover="onMediaItemDragOver($event, item)"
                            v-on:dragleave="dragOverMediaId = null"
                            v-on:drop="onMediaItemDrop($event, item)"
                        >
                            <div v-if="isSelecting" class="absolute top-1.5 left-1.5 z-10" v-on:click.stop="toggleSelect(item.id)">
                                <CheckSquare v-if="selectedIds.has(item.id)" class="w-5 h-5 text-accent-400 drop-shadow" :stroke-width="2" />
                                <Square v-else class="w-5 h-5 text-white drop-shadow" :stroke-width="2" />
                            </div>
                            <div class="relative aspect-square bg-surface-2 flex items-center justify-center overflow-hidden cursor-pointer">
                                <AppImage
                                    v-if="item.isImage"
                                    :src="item.thumbnailUrl ?? item.url"
                                    :alt="item.alt ?? ''"
                                    object-fit="cover"
                                    :focal-point="item.focalPositionCss ?? '50% 50%'"
                                />
                                <ImageIcon v-else class="w-10 h-10 text-muted" :stroke-width="1.5" />
                                <div v-if="!item.alt" class="absolute top-1 right-1 px-1.5 py-0.5 rounded text-xs font-medium bg-rose-500/80 text-white">{{ t("admin.media.missingAlt") }}</div>
                                <div v-if="!isSelecting" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5">
                                    <button type="button" class="p-1.5 bg-white/20 hover:bg-white/40 rounded-lg text-white transition-colors" :title="t('admin.media.preview')" v-on:click.stop="previewMedia = item">
                                        <Eye class="w-4 h-4" :stroke-width="2" />
                                    </button>
                                    <button type="button" class="p-1.5 bg-white/20 hover:bg-white/40 rounded-lg text-white transition-colors" :title="t('admin.media.copyUrl')" v-on:click.stop="copyUrl(item)">
                                        <Copy class="w-4 h-4" :stroke-width="2" />
                                    </button>
                                    <button type="button" class="p-1.5 bg-white/20 hover:bg-white/40 rounded-lg text-white transition-colors" :title="t('admin.media.qrCode')" v-on:click.stop="openQr(item)">
                                        <QrCode class="w-4 h-4" :stroke-width="2" />
                                    </button>
                                </div>
                            </div>
                            <div class="p-2 space-y-0.5">
                                <div class="text-xs font-medium text-primary truncate">{{ item.originalName }}</div>
                                <div class="text-xs text-muted">{{ formatSize(item.size) }}<span v-if="item.width"> · {{ item.width }}×{{ item.height }}</span></div>
                                <div v-if="searchQuery && item.folderName" class="text-xs text-accent-400/80 truncate flex items-center gap-1">
                                    <Folder class="w-2.5 h-2.5 shrink-0" :stroke-width="2" />{{ item.folderName }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="border border-line/60 rounded-xl overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-surface-2 text-xs text-muted uppercase">
                                <tr>
                                    <th v-if="isSelecting" class="w-8 px-3 py-2" />
                                    <th class="px-3 py-2 text-left">{{ t("admin.media.filename") }}</th>
                                    <th class="px-3 py-2 text-left hidden sm:table-cell">{{ t("admin.media.mimeType") }}</th>
                                    <th class="px-3 py-2 text-right hidden md:table-cell">{{ t("admin.media.size") }}</th>
                                    <th class="px-3 py-2 text-right w-24">{{ t("shared.common.actions") }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/40">
                                <tr
                                    v-for="item in displayedMedia"
                                    :key="item.id"
                                    class="hover:bg-surface-2 transition-colors cursor-pointer"
                                    :class="selectedIds.has(item.id) ? 'bg-accent-500/5' : ''"
                                    v-on:click="isSelecting ? toggleSelect(item.id) : openEditMedia(item)"
                                >
                                    <td v-if="isSelecting" class="px-3 py-2" v-on:click.stop="toggleSelect(item.id)">
                                        <CheckSquare v-if="selectedIds.has(item.id)" class="w-4 h-4 text-accent-400" :stroke-width="2" />
                                        <Square v-else class="w-4 h-4 text-muted" :stroke-width="2" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <AppImage
                                                :src="item.isImage ? (item.thumbnailUrl ?? item.url) : null"
                                                :alt="item.alt ?? ''"
                                                object-fit="cover"
                                                rounded="rounded"
                                                class="w-8 h-8 shrink-0"
                                            />
                                            <div class="min-w-0">
                                                <div class="truncate font-medium text-primary">{{ item.originalName }}</div>
                                                <div v-if="searchQuery && item.folderName" class="text-xs text-accent-400/80 flex items-center gap-1">
                                                    <Folder class="w-2.5 h-2.5 shrink-0" :stroke-width="2" />{{ item.folderName }}
                                                </div>
                                            </div>
                                            <span v-if="!item.alt" class="shrink-0 text-xs px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-500">alt</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-muted hidden sm:table-cell">{{ item.mimeType }}</td>
                                    <td class="px-3 py-2 text-right text-muted hidden md:table-cell">{{ formatSize(item.size) }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex justify-end gap-1" v-on:click.stop>
                                            <button type="button" class="p-1 text-muted hover:text-primary rounded transition-colors" v-on:click="previewMedia = item"><Eye class="w-3.5 h-3.5" :stroke-width="2" /></button>
                                            <button type="button" class="p-1 text-muted hover:text-primary rounded transition-colors" v-on:click="copyUrl(item)"><Copy class="w-3.5 h-3.5" :stroke-width="2" /></button>
                                            <button type="button" class="p-1 text-muted hover:text-primary rounded transition-colors" v-on:click="openQr(item)"><QrCode class="w-3.5 h-3.5" :stroke-width="2" /></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </main>
        </div>

        <AppModal :show="!!editingMedia" max-width="3xl" scrollable v-on:close="closeEditMedia">
            <div class="flex items-center justify-between gap-4 mb-1">
                <h3 class="text-lg font-semibold text-primary truncate">{{ t("admin.media.editMedia") }}</h3>
                <div class="flex border border-line/60 rounded-lg p-0.5 shrink-0">
                    <button type="button" class="px-3 py-1 rounded text-xs transition-colors" :class="editTab === 'edit' ? 'bg-surface-3 text-primary' : 'text-muted hover:text-primary'" v-on:click="editTab = 'edit'">{{ t("admin.media.tabEdit") }}</button>
                    <button type="button" class="px-3 py-1 rounded text-xs transition-colors" :class="editTab === 'history' ? 'bg-surface-3 text-primary' : 'text-muted hover:text-primary'" v-on:click="openHistoryTab">{{ t("admin.media.tabHistory") }}</button>
                    <button type="button" class="px-3 py-1 rounded text-xs transition-colors" :class="editTab === 'usage' ? 'bg-surface-3 text-primary' : 'text-muted hover:text-primary'" v-on:click="editTab = 'usage'; if (!mediaUsage) loadUsage()">{{ t("admin.media.tabUsage") }}</button>
                </div>
            </div>

            <div v-if="editTab === 'usage'" class="space-y-3 min-h-32">
                <div v-if="!mediaUsage" class="text-center py-8 text-muted text-sm">{{ t("shared.common.loading") }}</div>
                <template v-else>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-surface-2 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-primary">{{ mediaUsage.total }}</div>
                            <div class="text-xs text-muted mt-1">{{ t("admin.media.usageTotal") }}</div>
                        </div>
                        <div class="bg-surface-2 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-primary">{{ mediaUsage.directCount }}</div>
                            <div class="text-xs text-muted mt-1">{{ t("admin.media.usageDirect") }}</div>
                        </div>
                        <div class="bg-surface-2 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-primary">{{ mediaUsage.contentCount }}</div>
                            <div class="text-xs text-muted mt-1">{{ t("admin.media.usageContent") }}</div>
                        </div>
                    </div>
                    <p v-if="mediaUsage.total === 0" class="text-xs text-muted text-center">{{ t("admin.media.usageNone") }}</p>
                </template>
            </div>

            <div v-if="editTab === 'history'" class="space-y-2 min-h-32">
                <div v-if="historyLoading" class="text-center py-8 text-muted text-sm">{{ t("shared.common.loading") }}</div>
                <div v-else-if="!mediaHistory.length" class="text-center py-8 text-muted text-sm">{{ t("admin.media.noHistory") }}</div>
                <div v-else class="divide-y divide-line/40">
                    <div v-for="entry in mediaHistory" :key="entry.id" class="py-2.5 flex items-start gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-primary">{{ historyActionLabel(entry.action) }}</div>
                            <div class="text-xs text-muted">{{ entry.userName ?? entry.userEmail ?? t("shared.common.unknown") }}</div>
                        </div>
                        <div class="text-xs text-muted shrink-0">{{ formatDateTime(entry.createdAt) }}</div>
                    </div>
                </div>
            </div>

            <form v-else class="grid grid-cols-1 md:grid-cols-2 gap-4" v-on:submit.prevent="submitMediaEdit">
                <div class="space-y-2">
                    <div
                        v-if="editingMedia?.isImage"
                        class="relative bg-surface-2 rounded-md overflow-hidden cursor-crosshair select-none"
                        v-on:click="onFocalPointClick"
                    >
                        <img :src="editingMedia.url" :alt="editingMedia.alt ?? ''" class="w-full h-auto block">
                        <div
                            v-if="editForm.focalX !== null && editForm.focalY !== null"
                            class="absolute w-6 h-6 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-accent-500 shadow-lg pointer-events-none"
                            :style="{ left: `${editForm.focalX * 100}%`, top: `${editForm.focalY * 100}%` }"
                        />
                    </div>
                    <div v-else class="bg-surface-2 rounded-md p-8 flex flex-col items-center">
                        <ImageIcon class="w-16 h-16 text-muted" :stroke-width="1.5" />
                        <p class="mt-2 text-xs text-muted">{{ editingMedia?.mimeType }}</p>
                    </div>
                    <div v-if="editingMedia?.isImage" class="flex items-center justify-between text-xs text-muted">
                        <span>{{ t("admin.media.focalHint") }}</span>
                        <button
                            v-if="editForm.focalX !== null"
                            type="button"
                            class="text-accent-400 hover:underline"
                            v-on:click="resetFocalPoint"
                        >
                            {{ t("admin.media.resetFocal") }}
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <AppInput
                        v-model="editForm.alt"
                        :label="t('admin.media.alt')"
                        :error="editErrors.alt ?? ''"
                        :placeholder="t('admin.media.altPlaceholder')"
                    />
                    <AppTextarea
                        v-model="editForm.caption"
                        :label="t('admin.media.caption')"
                        :placeholder="t('admin.media.captionPlaceholder')"
                        :rows="3"
                    />
                    <AppMultiselect
                        v-model="editForm.folderId"
                        :options="folderEditOptions"
                        :label="t('admin.media.folder')"
                        :placeholder="t('admin.media.rootFolder')"
                        :allow-empty="true"
                        track-by="id"
                        option-label="displayLabel"
                    />

                    <dl class="text-xs text-muted space-y-0.5 pt-2 border-t border-line">
                        <div class="flex justify-between"><dt>ID</dt><dd class="font-mono select-all">{{ editingMedia?.id }}</dd></div>
                        <div class="flex justify-between"><dt>{{ t("admin.media.filename") }}</dt><dd class="font-mono">{{ editingMedia?.originalName }}</dd></div>
                        <div class="flex justify-between"><dt>{{ t("admin.media.size") }}</dt><dd>{{ formatSize(editingMedia?.size ?? 0) }}</dd></div>
                        <div v-if="editingMedia?.width" class="flex justify-between"><dt>{{ t("admin.media.dimensions") }}</dt><dd>{{ editingMedia.width }}×{{ editingMedia.height }}</dd></div>
                        <div class="flex justify-between"><dt>{{ t("admin.media.mimeType") }}</dt><dd>{{ editingMedia?.mimeType }}</dd></div>
                        <div v-if="editingMedia?.uploadedBy" class="flex justify-between"><dt>{{ t("admin.media.uploadedBy") }}</dt><dd>{{ editingMedia.uploadedBy }}</dd></div>
                        <div v-if="editingMedia?.createdAt" class="flex justify-between"><dt>{{ t("admin.media.createdAt") }}</dt><dd>{{ formatDateTime(editingMedia.createdAt) }}</dd></div>
                        <div v-if="editingMedia?.updatedAt" class="flex justify-between"><dt>{{ t("admin.media.updatedAt") }}</dt><dd>{{ formatDateTime(editingMedia.updatedAt) }}</dd></div>
                        <div v-if="editingMedia?.permalink" class="flex justify-between items-center gap-2 pt-1 border-t border-line/40">
                            <dt class="shrink-0">{{ t("admin.media.permalink") }}</dt>
                            <dd class="font-mono text-xs text-accent-400 truncate cursor-pointer hover:underline" :title="editingMedia.permalink" v-on:click="copyUrl(editingMedia)">{{ editingMedia.permalink }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="md:col-span-2 flex flex-col sm:flex-row sm:items-center gap-2 pt-2 border-t border-line">
                    <AppButton
                        type="submit"
                        variant="primary"
                        size="md"
                        class="w-full sm:w-auto order-1 sm:order-2 sm:ms-auto"
                        :loading="editSaving"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}
                    </AppButton>
                    <AppButton
                        v-if="editingMedia?.isImage"
                        variant="secondary"
                        size="md"
                        class="w-full sm:w-auto order-2 sm:order-1"
                        v-on:click="openCrop(editingMedia); editingMedia = null"
                    >
                        <Crop class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("admin.media.crop") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" class="w-full sm:w-auto order-3 sm:order-1" v-on:click="deletingMedia = editingMedia; editingMedia = null">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                    <AppButton variant="ghost" size="md" class="w-full sm:w-auto order-2 sm:order-1" v-on:click="openQr(editingMedia)">
                        <QrCode class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("admin.media.qrCode") }}
                    </AppButton>
                    <AppButton variant="ghost" size="md" class="w-full sm:w-auto order-4 sm:order-3" v-on:click="closeEditMedia">{{ t("shared.common.cancel") }}</AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal :show="folderModal.open" max-width="md" v-on:close="folderModal.open = false">
            <h3 class="text-lg font-semibold text-primary">
                {{ folderModal.editing ? t("admin.media.editFolder") : t("admin.media.createFolder") }}
            </h3>
            <form class="space-y-4" v-on:submit.prevent="submitFolder">
                <AppInput
                    v-model="folderForm.name"
                    :label="t('admin.media.folderName')"
                    :error="folderModal.errors.name ?? ''"
                />
                <AppMultiselect
                    v-model="folderForm.parentId"
                    :options="folderParentSelectOptions"
                    :label="t('admin.media.parentFolder')"
                    :placeholder="t('admin.media.rootFolder')"
                    :allow-empty="true"
                    track-by="id"
                    option-label="displayLabel"
                />
                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="folderModal.open = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="folderModal.saving"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal :show="pendingBulkDelete" max-width="sm" v-on:close="pendingBulkDelete = false">
            <h3 class="text-base font-semibold text-primary">{{ t("admin.media.bulkDeleteConfirm", { count: selectedIds.size }) }}</h3>
            <p class="text-sm text-secondary">{{ t("admin.media.bulkDeleteConfirmDesc") }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingBulkDelete = false">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" :loading="bulkDeleteLoading" v-on:click="doBulkDelete">{{ t("shared.common.delete") }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <AppModal :show="!!deletingMedia" max-width="sm" v-on:close="deletingMedia = null">
            <p class="text-sm text-primary">{{ t("admin.media.deleteConfirm", { name: deletingMedia?.originalName }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="deletingMedia = null">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDeleteMedia">{{ t("shared.common.delete") }}</AppButton>
            </div>
        </AppModal>

        <AppModal :show="!!deletingFolder" max-width="sm" v-on:close="deletingFolder = null">
            <p class="text-sm text-primary">{{ t("admin.media.deleteFolderConfirm", { name: deletingFolder?.name }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="deletingFolder = null">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDeleteFolder">{{ t("shared.common.delete") }}</AppButton>
            </div>
        </AppModal>

        <AppModal :show="!!previewMedia" max-width="4xl" v-on:close="previewMedia = null">
            <div class="flex items-start justify-between gap-4 mb-3">
                <h3 class="text-sm font-medium text-primary truncate">{{ previewMedia?.originalName }}</h3>
                <div class="flex gap-2 shrink-0">
                    <AppButton size="sm" variant="ghost" v-on:click="copyUrl(previewMedia)">
                        <Copy class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("admin.media.copyUrl") }}
                    </AppButton>
                    <AppButton size="sm" variant="ghost" v-on:click="openQr(previewMedia)">
                        <QrCode class="w-3.5 h-3.5" :stroke-width="2" /> QR
                    </AppButton>
                </div>
            </div>
            <div class="flex items-center justify-center bg-surface-2 rounded-xl min-h-48">
                <AppImage
                    v-if="previewMedia?.isImage"
                    :src="previewMedia.url"
                    :alt="previewMedia.alt ?? ''"
                    object-fit="contain"
                    rounded="rounded-lg"
                    class="max-w-full max-h-[70vh]"
                />
                <div v-else class="flex flex-col items-center gap-3 py-12">
                    <ImageIcon class="w-16 h-16 text-muted" :stroke-width="1.5" />
                    <p class="text-sm text-muted">{{ previewMedia?.mimeType }}</p>
                    <a :href="previewMedia?.url" target="_blank" class="text-sm text-accent-400 hover:underline">{{ t("admin.media.open") }}</a>
                </div>
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted">
                <div class="flex justify-between"><dt>{{ t("admin.media.size") }}</dt><dd>{{ formatSize(previewMedia?.size ?? 0) }}</dd></div>
                <div v-if="previewMedia?.width" class="flex justify-between"><dt>{{ t("admin.media.dimensions") }}</dt><dd>{{ previewMedia.width }}×{{ previewMedia.height }}</dd></div>
            </dl>
        </AppModal>

        <MediaQrModal :media="qrMedia" v-on:close="qrMedia = null" />

        <AppModal :show="openBulkMove" max-width="sm" v-on:close="openBulkMove = false">
            <h3 class="text-sm font-semibold text-primary mb-3">{{ t("admin.media.bulkMove", { count: selectedIds.size }) }}</h3>
            <AppMultiselect
                v-model="bulkMoveTargetId"
                :options="[{ id: null, displayLabel: t('admin.media.rootFolder') }, ...allFlatFolders.map(f => ({ id: f.id, displayLabel: '  '.repeat(f.depth) + f.name }))]"
                :label="t('admin.media.folder')"
                :placeholder="t('admin.media.rootFolder')"
                :allow-empty="true"
                track-by="id"
                option-label="displayLabel"
            />
            <div class="flex justify-end gap-2 mt-4">
                <AppButton variant="ghost" size="md" v-on:click="openBulkMove = false">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="primary" size="md" v-on:click="bulkMove(); openBulkMove = false"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
            </div>
        </AppModal>

        <MediaCropperModal
            :media="cropMedia"
            :crop-path="cropPath"
            v-on:close="cropMedia = null"
            v-on:cropped="onCropped"
        />
    </div>
</template>
