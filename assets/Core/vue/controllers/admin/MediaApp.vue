<script setup>
import { HttpMethod } from "@/shared/utils/httpMethod.js";
import { ref, reactive, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { Pencil, Trash2, Plus, Folder, Upload, Image as ImageIcon, ChevronRight, ChevronDown, Home } from "lucide-vue-next";
import AppButton from "@/shared/components/AppButton.vue";
import AppIconButton from "@/shared/components/AppIconButton.vue";
import AppInput from "@/shared/components/AppInput.vue";
import AppSearchInput from "@/shared/components/AppSearchInput.vue";
import AppTextarea from "@/shared/components/AppTextarea.vue";
import AppMultiselect from "@/shared/components/AppMultiselect.vue";
import AppModal from "@/shared/components/AppModal.vue";
import AppMessage from "@/shared/components/AppMessage.vue";
import AppNoData from "@/shared/components/AppNoData.vue";
import { useFileSize } from "@/shared/composables/useFileSize.js";

const { t } = useI18n();
const { formatSize } = useFileSize();

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
});

const folders = ref([...props.folders]);
const media = ref([...props.media]);
const currentFolderId = ref(props.currentFolderId);
const searchQuery = ref(props.search ?? "");

function navigateTo(folderId) {
    const url = new URL("/admin/media", window.location.origin);
    if (folderId) url.searchParams.set("folderId", String(folderId));
    if (searchQuery.value) url.searchParams.set("search", searchQuery.value);
    window.location.href = url.toString();
}

function runSearch() {
    navigateTo(currentFolderId.value);
}

// ── Folder tree helpers ──────────────────────────────────────────────────────
function buildFolderTree(list) {
    const byId = new Map(list.map((f) => [f.id, { ...f, children: [] }]));
    const roots = [];
    for (const node of byId.values()) {
        if (node.parentId && byId.has(node.parentId)) {
            byId.get(node.parentId).children.push(node);
        } else {
            roots.push(node);
        }
    }
    const sort = (nodes) => {
        nodes.sort((a, b) => a.name.localeCompare(b.name));
        nodes.forEach((n) => sort(n.children));
    };
    sort(roots);
    return roots;
}

function flattenFolders(nodes, depth = 0, skipDescendantsOf = null) {
    const result = [];
    for (const node of nodes) {
        result.push({
            ...node,
            depth,
            childCount: node.children.length,
            mediaCount: node.mediaCount ?? 0,
        });
        const collapsed = skipDescendantsOf?.has(node.id) ?? false;
        if (node.children.length && !collapsed) {
            result.push(...flattenFolders(node.children, depth + 1, skipDescendantsOf));
        }
    }
    return result;
}

const folderTree = computed(() => buildFolderTree(folders.value));

// Folders collapsed by the user (the ones whose children are hidden in the tree).
const collapsedFolderIds = ref(loadCollapsedFolderIds());

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

// ── Upload ───────────────────────────────────────────────────────────────────
const uploadInput = ref(null);
const uploading = ref(false);
const filesDragOver = ref(false);

async function uploadFileList(files) {
    if (!files.length) return;
    uploading.value = true;
    try {
        for (const file of files) {
            const body = new FormData();
            body.append("image", file);
            if (currentFolderId.value) body.append("folderId", String(currentFolderId.value));
            const response = await fetch(props.uploadPath, { method: HttpMethod.Post, body });
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (data.media) media.value.unshift(data.media);
        }
        toast.success(t("admin.media.uploaded", { count: files.length }));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        uploading.value = false;
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
const editForm = reactive({ alt: "", caption: "", focalX: null, focalY: null, folderId: null });
const editErrors = ref({});
const editSaving = ref(false);

function openEditMedia(item) {
    editingMedia.value = item;
    editErrors.value = {};
    Object.assign(editForm, {
        alt: item.alt ?? "",
        caption: item.caption ?? "",
        focalX: item.focalX,
        focalY: item.focalY,
        folderId: item.folderId,
    });
}

function closeEditMedia() {
    editingMedia.value = null;
}

async function submitMediaEdit() {
    if (!editingMedia.value) return;
    editSaving.value = true;
    editErrors.value = {};
    try {
        const url = props.editPath.replace("__id__", editingMedia.value.id);
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
        const response = await fetch(props.deletePath.replace("__id__", item.id), { method: HttpMethod.Post });
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
            ? props.folderEditPath.replace("__id__", folderModal.editing.id)
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
        const response = await fetch(props.folderDeletePath.replace("__id__", folder.id), { method: HttpMethod.Post });
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

function onMediaItemDragOver(event, mediaItem) {
    if (!event.dataTransfer.types.includes("application/x-aurora-media")) return;
    event.preventDefault();
    event.stopPropagation();
    dragOverMediaId.value = mediaItem.id;
    dragOverFolderId.value = null;
}

async function onMediaItemDrop(event, targetItem) {
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
        const response = await fetch(props.movePath.replace("__id__", mediaId), {
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
        const response = await fetch(props.folderEditPath.replace("__id__", folderId), {
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
        <!-- Top toolbar: breadcrumbs + search + upload -->
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
                        v-on:search="runSearch"
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
            <!-- Folder sidebar -->
            <aside class="lg:w-72 shrink-0 space-y-2">
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
                            <AppIconButton color="accent" v-on:click="openEditFolder(folder)">
                                <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton color="rose" v-on:click="deletingFolder = folder">
                                <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                            </AppIconButton>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main -->
            <main
                class="flex-1 min-w-0 min-h-[60vh] space-y-4 relative"
                v-on:dragover="onMainDragOver"
                v-on:dragleave="onMainDragLeave"
                v-on:drop="onMainDrop"
            >
                <div
                    v-if="filesDragOver"
                    class="absolute inset-0 z-10 rounded-xl border-2 border-dashed border-accent-400 bg-accent-500/10 flex flex-col items-center justify-center gap-3 pointer-events-none"
                >
                    <Upload class="w-14 h-14 text-accent-400" :stroke-width="1.5" />
                    <span class="text-base font-medium text-accent-400">{{ t("admin.media.dropToUpload") }}</span>
                </div>
                <AppMessage v-if="media.some((m) => !m.alt)" variant="warning">
                    {{ t("admin.media.altWarning") }}
                </AppMessage>

                <AppNoData v-if="!media.length" :message="t('admin.media.empty')" />
                <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                    <div
                        v-for="item in media"
                        :key="item.id"
                        class="group relative bg-surface border rounded-lg overflow-hidden cursor-pointer transition-colors"
                        :class="dragOverMediaId === item.id
                            ? 'border-accent-400 ring-2 ring-accent-400/50'
                            : 'border-line/60 hover:border-accent-400'"
                        draggable="true"
                        v-on:click="openEditMedia(item)"
                        v-on:dragstart="onMediaDragStart($event, item)"
                        v-on:dragover="onMediaItemDragOver($event, item)"
                        v-on:dragleave="dragOverMediaId = null"
                        v-on:drop="onMediaItemDrop($event, item)"
                    >
                        <div class="relative aspect-square bg-surface-2 flex items-center justify-center overflow-hidden">
                            <img
                                v-if="item.isImage"
                                :src="item.thumbnailUrl ?? item.url"
                                :alt="item.alt ?? ''"
                                class="w-full h-full object-cover"
                                :style="{ objectPosition: item.focalPositionCss ?? '50% 50%' }"
                            >
                            <ImageIcon v-else class="w-10 h-10 text-muted" :stroke-width="1.5" />
                            <div v-if="!item.alt" class="absolute top-1 right-1 px-1.5 py-0.5 rounded text-xs font-medium bg-rose-500/80 text-white">
                                {{ t("admin.media.missingAlt") }}
                            </div>
                        </div>
                        <div class="p-2 space-y-0.5">
                            <div class="text-xs font-medium text-primary truncate">{{ item.originalName }}</div>
                            <div class="text-xs text-muted">{{ formatSize(item.size) }}<span v-if="item.width"> · {{ item.width }}×{{ item.height }}</span></div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Edit media modal -->
        <AppModal :show="!!editingMedia" max-width="3xl" scrollable v-on:close="closeEditMedia">
            <h3 class="text-lg font-semibold text-primary">{{ t("admin.media.editMedia") }}</h3>
            <form class="grid grid-cols-1 md:grid-cols-2 gap-4" v-on:submit.prevent="submitMediaEdit">
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
                        :required="true"
                        :error="editErrors.alt ?? ''"
                        :placeholder="t('admin.media.altPlaceholder')"
                    />
                    <AppTextarea
                        v-model="editForm.caption"
                        :label="t('admin.media.caption')"
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
                        {{ t("shared.common.save") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" class="w-full sm:w-auto order-2 sm:order-1" v-on:click="deletingMedia = editingMedia; editingMedia = null">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                    <AppButton variant="ghost" size="md" class="w-full sm:w-auto order-3 sm:order-3" v-on:click="closeEditMedia">{{ t("shared.common.cancel") }}</AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Folder modal -->
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
                    <AppButton type="submit" variant="primary" size="md" :loading="folderModal.saving">{{ t("shared.common.save") }}</AppButton>
                </div>
            </form>
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
    </div>
</template>
