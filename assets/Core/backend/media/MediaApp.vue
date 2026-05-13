<script setup>
import { onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { Pencil, Trash2, Plus, Folder, Upload, Image as ImageIcon, Film, FileText, Play, ChevronRight, ChevronDown, Home, Copy, QrCode, LayoutGrid, List, SortAsc, SortDesc, CheckSquare, Square, X, Move, HardDrive, Eye, Save, Star, Crop, Layers, Images } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppFilePickerButton from "@/shared/components/action/AppFilePickerButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppOverlayIconButton from "@/shared/components/action/AppOverlayIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppProgressBar from "@/shared/components/feedback/AppProgressBar.vue";
import AppSelectionCheck from "@/shared/components/feedback/AppSelectionCheck.vue";
import AppImage from "@/shared/components/display/AppImage.vue";
import { useFileSize } from "@/shared/composables/format/useFileSize.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { useMultiSelection } from "@/shared/composables/list/useMultiSelection.js";
import MediaQrModal from "@core/backend/media/MediaQrModal.vue";
import PdfThumbnail from "@core/backend/media/components/PdfThumbnail.vue";
import MediaCropperModal from "@core/backend/media/MediaCropperModal.vue";
import { useMediaNavigation } from "@core/backend/media/composables/useMediaNavigation.js";
import { useMediaFolderTree } from "@core/backend/media/composables/useMediaFolderTree.js";
import { useMediaDisplay, TYPE_FILTERS } from "@core/backend/media/composables/useMediaDisplay.js";
import { useMediaUpload } from "@core/backend/media/composables/useMediaUpload.js";
import { useMediaEdit } from "@core/backend/media/composables/useMediaEdit.js";
import { useMediaDelete } from "@core/backend/media/composables/useMediaDelete.js";
import { useMediaFolders } from "@core/backend/media/composables/useMediaFolders.js";
import { useMediaBulkActions } from "@core/backend/media/composables/useMediaBulkActions.js";
import { useMediaDragDrop } from "@core/backend/media/composables/useMediaDragDrop.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();
const { formatSize } = useFileSize();
const { formatDateTime } = useDateFormat();

const props = defineProps({
    folders: { type: Array, default: () => [] },
    media: { type: Array, default: () => [] },
    currentFolderId: { type: Number, default: null },
    search: { type: String, default: "" },
    uploadPath: { type: String, default: "/backend/media/upload" },
    editPath: { type: String, default: "/backend/media/__id__/edit" },
    deletePath: { type: String, default: "/backend/media/__id__/delete" },
    movePath: { type: String, default: "/backend/media/__id__/move" },
    folderCreatePath: { type: String, default: "/backend/media/folders" },
    folderEditPath: { type: String, default: "/backend/media/folders/__id__/edit" },
    folderDeletePath: { type: String, default: "/backend/media/folders/__id__/delete" },
    reorderPath: { type: String, default: "/backend/media/reorder" },
    bulkDeletePath: { type: String, default: "/backend/media/bulk-delete" },
    listPath: { type: String, default: "/backend/media/list" },
    bulkMovePath: { type: String, default: "/backend/media/bulk-move" },
    cropPath: { type: String, default: "/backend/media/__id__/crop" },
    totalStorageBytes: { type: Number, default: 0 },
    /**
     * Extra fields to register on the media edit form. Lets clients extend
     * the modal without forking this component.
     * Example: { tags: { default: '', fromEntity: (m) => m.tags ?? '' } }
     */
    extraFields: { type: Object, default: () => ({}) },
});

const { selectedIds, isSelecting, toggle: toggleSelect, clear: clearSelection } = useMultiSelection();

const { media, folders, currentFolderId, allMediaView, searchQuery, mediaLoading, loadMedia, navigateTo, navigateToAll, focusMediaFromQuery } =
    useMediaNavigation(props, clearSelection);

const { folderTree, flatFolders, allFlatFolders, currentFolder, breadcrumbs, childFolders, collapsedFolderIds, toggleCollapse, favouriteFolderIds, toggleFavourite, favouriteFolders } =
    useMediaFolderTree(folders, currentFolderId);

const { viewMode, setViewMode, typeFilter, sortBy, sortDir, setSort, displayedMedia, reorderEnabled } =
    useMediaDisplay(media);

const { uploadInput, uploading, uploadProgress, filesDragOver, uploadFiles, onMainDragOver, onMainDragLeave, onMainDrop } =
    useMediaUpload(props, media, currentFolderId);

const { editingMedia, editTab, mediaHistory, mediaUsage, historyLoading, editForm, editErrors, editSaving, openEditMedia, closeEditMedia, loadHistory, loadUsage, openHistoryTab, submitMediaEdit, onFocalPointClick, resetFocalPoint, previewMedia, cropMedia, openCrop, onCropped, qrMedia, openQr, copyUrl, mediaPermalink, historyActionLabel } =
    useMediaEdit(props, media);

const { deletingMedia, deletingMediaUsage, deletingMediaUsageLoading, askDeleteMedia, confirmDeleteMedia } =
    useMediaDelete(props, media, editingMedia);

const { folderModal, folderForm, openCreateFolder, openEditFolder, submitFolder, deletingFolder, confirmDeleteFolder, folderParentOptions, folderEditOptions, folderParentSelectOptions } =
    useMediaFolders(props, folders, currentFolderId, flatFolders, allFlatFolders, navigateTo);

const { selectAll, pendingBulkDelete, bulkDeleteLoading, doBulkDelete, bulkMoveTargetId, openBulkMove, bulkMove } =
    useMediaBulkActions(props, media, selectedIds, clearSelection, currentFolderId, displayedMedia);

const { dragOverFolderId, dragOverMediaId, rootDragOver, onMediaDragStart, onFolderDragStart, onFolderDragOver, onRootDragOver, onDragLeave, onMediaItemDragOver, onMediaItemDrop, onFolderDrop } =
    useMediaDragDrop(props, media, folders, currentFolderId, reorderEnabled);

onMounted(() => focusMediaFromQuery(openEditMedia));
</script>

<template>
    <div class="flex flex-col gap-4 min-h-[calc(100vh-8rem)]">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 bg-surface border border-line/60 rounded-xl px-4 py-3">
            <nav class="flex items-center gap-1 text-sm text-muted min-w-0 flex-1 flex-wrap">
                <template v-if="allMediaView">
                    <span class="flex items-center gap-1.5 text-primary shrink-0">
                        <Layers class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("backend.media.allMedia") }}
                    </span>
                </template>
                <template v-else>
                    <button type="button" class="flex items-center gap-1.5 hover:text-primary transition-colors shrink-0" v-on:click="navigateTo(null)">
                        <Home class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("backend.media.rootFolder") }}
                    </button>
                    <template v-for="crumb in breadcrumbs" :key="crumb.id">
                        <ChevronRight class="w-3 h-3 shrink-0" :stroke-width="2" />
                        <button type="button" class="hover:text-primary transition-colors truncate" v-on:click="navigateTo(crumb.id)">{{ crumb.name }}</button>
                    </template>
                </template>
            </nav>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:shrink-0">
                <div class="w-full sm:w-64">
                    <AppSearchInput
                        v-model="searchQuery"
                        :placeholder="t('backend.media.searchPlaceholder')"
                        v-on:search="(q) => navigateTo(currentFolderId, q)"
                    />
                </div>
                <AppFilePickerButton
                    v-if="can('core.media.manage')"
                    ref="uploadInput"
                    accept="image/*"
                    multiple
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    :loading="uploading"
                    v-on:change="uploadFiles"
                >
                    <Upload class="w-4 h-4" :stroke-width="2" />
                    {{ t("backend.media.upload") }}
                </AppFilePickerButton>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-4">
            <aside class="lg:w-72 shrink-0 space-y-2">
                <div v-if="favouriteFolders.length" class="space-y-0.5">
                    <h3 class="text-xs font-semibold text-secondary uppercase tracking-wide flex items-center gap-1.5 px-1">
                        <Star class="w-3 h-3 text-amber-400" :stroke-width="2" fill="currentColor" />
                        {{ t("backend.media.favourites") }}
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
                    <h2 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("backend.media.folders") }}</h2>
                    <AppIconButton
                        v-if="can('core.media.manage')"
                        :title="t('backend.media.newFolder')"
                        v-on:click="openCreateFolder"
                    >
                        <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                    </AppIconButton>
                </div>
                <div class="space-y-0.5">
                    <button
                        type="button"
                        class="w-full text-left px-3 py-2 rounded-lg transition-colors flex items-center gap-2 border"
                        :class="allMediaView
                            ? 'bg-accent-600/15 text-accent-400 border-accent-600/30'
                            : 'hover:bg-surface-2 text-primary border-transparent'"
                        v-on:click="navigateToAll()"
                    >
                        <Layers class="w-4 h-4 shrink-0" :stroke-width="2" />
                        <span class="flex-1 text-sm font-medium">{{ t("backend.media.allMedia") }}</span>
                    </button>
                    <button
                        type="button"
                        class="w-full text-left px-3 py-2 rounded-lg transition-colors flex items-center gap-2 border"
                        :class="[
                            !currentFolderId && !allMediaView
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
                        <span class="flex-1 text-sm font-medium">{{ t("backend.media.rootFolder") }}</span>
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
                            :title="collapsedFolderIds.has(folder.id) ? t('backend.media.expand') : t('backend.media.collapse')"
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
                                :title="favouriteFolderIds.has(folder.id) ? t('backend.media.unfavourite') : t('backend.media.favourite')"
                                v-on:click.stop="toggleFavourite(folder.id)"
                            >
                                <Star class="w-3.5 h-3.5" :stroke-width="2" :fill="favouriteFolderIds.has(folder.id) ? 'currentColor' : 'none'" />
                            </button>
                            <AppIconButton v-if="can('core.media.manage')" color="accent" v-on:click="openEditFolder(folder)">
                                <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton v-if="can('core.media.manage')" color="rose" v-on:click="deletingFolder = folder">
                                <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                            </AppIconButton>
                        </div>
                    </div>
                </div>
                <div v-if="totalStorageBytes > 0" class="pt-3 border-t border-line/40 space-y-1.5">
                    <div class="flex items-center justify-between text-xs text-muted">
                        <span class="flex items-center gap-1"><HardDrive class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.media.storage") }}</span>
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
                    <span class="text-base font-medium text-accent-400">{{ t("backend.media.dropToUpload") }}</span>
                </div>

                <div v-if="uploadProgress.length" class="space-y-1.5 bg-surface border border-line/60 rounded-xl p-3">
                    <div v-for="(f, i) in uploadProgress" :key="i" class="space-y-1">
                        <div class="flex justify-between text-xs text-muted">
                            <span class="truncate max-w-[80%]">{{ f.name }}</span>
                            <span>{{ f.percent }}%</span>
                        </div>
                        <AppProgressBar :value="f.percent" size="sm" />
                    </div>
                </div>

                <div v-if="selectedIds.size" class="flex flex-wrap items-center gap-2 bg-accent-500/10 border border-accent-400/30 rounded-xl px-4 py-2.5">
                    <span class="text-sm font-medium text-accent-400">{{ selectedIds.size }} {{ t("backend.media.selected") }}</span>
                    <div class="flex gap-2 ml-auto flex-wrap">
                        <AppButton size="sm" variant="ghost" v-on:click="selectAll"><CheckSquare class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.media.selectAll") }}</AppButton>
                        <AppButton size="sm" variant="ghost" v-on:click="() => { bulkMoveTargetId = null; }" v-on:click.prevent="openBulkMove = true">
                            <Move class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("backend.media.move") }}
                        </AppButton>
                        <AppButton v-if="can('core.media.manage')" size="sm" variant="danger" v-on:click="pendingBulkDelete = true">
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
                                v-for="s in [{k:'position',l:'#',title:t('backend.media.sortPositionHint')},{k:'name',l:'A-Z',title:t('backend.media.sortName')},{k:'size',l:'KB',title:t('backend.media.sortSize')},{k:'date',l:t('backend.media.sortDate'),title:t('backend.media.sortDate')}]"
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

                <AppMessage v-if="media.some((m) => !m.alt)" variant="warning">{{ t("backend.media.altWarning") }}</AppMessage>

                <div v-if="mediaLoading" class="flex items-center justify-center py-16 text-muted text-sm">
                    <span class="animate-pulse">{{ t('shared.common.loading') }}</span>
                </div>

                <template v-else>
                    <div v-if="childFolders.length && !searchQuery" class="space-y-2">
                        <p class="text-xs text-muted uppercase tracking-wide px-1">{{ t("backend.media.subfolders") }}</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                            <button
                                v-for="folder in childFolders"
                                :key="`folder-${folder.id}`"
                                type="button"
                                class="group relative flex items-center gap-2 rounded-lg border border-line/60 bg-surface hover:border-accent-400 hover:bg-surface-2/40 transition-colors px-3 py-2.5 text-left"
                                v-on:click="navigateTo(folder.id)"
                                v-on:dblclick="navigateTo(folder.id)"
                            >
                                <Folder class="w-5 h-5 text-accent-400 shrink-0" :stroke-width="2" />
                                <span class="flex-1 text-sm text-primary truncate">{{ folder.name }}</span>
                                <span v-if="folder.mediaCount" class="text-xs text-muted shrink-0">{{ folder.mediaCount }}</span>
                            </button>
                        </div>
                    </div>

                    <AppNoData v-if="!displayedMedia.length && (!childFolders.length || searchQuery)" :message="t('backend.media.empty')" />

                    <p v-if="displayedMedia.length && childFolders.length && !searchQuery" class="text-xs text-muted uppercase tracking-wide px-1 mt-4">{{ t("backend.media.files") }}</p>

                    <div v-if="displayedMedia.length && viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                        <div
                            v-for="item in displayedMedia"
                            :key="item.id"
                            class="group relative bg-surface border rounded-lg overflow-hidden transition-colors cursor-pointer"
                            :class="[
                                dragOverMediaId === item.id ? 'border-accent-400 ring-2 ring-accent-400/50' : 'border-line/60 hover:border-accent-400',
                                selectedIds.has(item.id) ? 'ring-2 ring-accent-500' : '',
                            ]"
                            draggable="true"
                            v-on:click="isSelecting ? toggleSelect(item.id) : (previewMedia = item)"
                            v-on:dragstart="onMediaDragStart($event, item)"
                            v-on:dragover="onMediaItemDragOver($event, item)"
                            v-on:dragleave="dragOverMediaId = null"
                            v-on:drop="onMediaItemDrop($event, item)"
                        >
                            <div v-if="isSelecting" class="absolute top-1.5 left-1.5 z-10" v-on:click.stop="toggleSelect(item.id)">
                                <AppSelectionCheck :active="selectedIds.has(item.id)" />
                            </div>
                            <div class="relative aspect-square bg-surface-2 flex items-center justify-center overflow-hidden cursor-pointer">
                                <AppImage
                                    v-if="item.isImage"
                                    :src="item.thumbnailUrl ?? item.url"
                                    :alt="item.alt ?? ''"
                                    object-fit="cover"
                                    :focal-point="item.focalPositionCss ?? '50% 50%'"
                                />
                                <template v-else-if="item.isVideo">
                                    <video
                                        :src="item.url"
                                        class="w-full h-full object-cover"
                                        preload="metadata"
                                        muted
                                        playsinline
                                    />
                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                        <div class="bg-black/50 rounded-full p-2">
                                            <Play class="w-6 h-6 text-white fill-white" :stroke-width="0" />
                                        </div>
                                    </div>
                                </template>
                                <PdfThumbnail v-else-if="item.isPdf" :url="item.url" />
                                <ImageIcon v-else class="w-10 h-10 text-muted" :stroke-width="1.5" />
                                <div v-if="!item.alt" class="absolute top-1 right-1 px-1.5 py-0.5 rounded text-xs font-medium bg-rose-500/80 text-white">{{ t("backend.media.missingAlt") }}</div>
                                <div v-if="!isSelecting" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5">
                                    <AppOverlayIconButton size="sm" variant="light" :title="t('backend.media.preview')" v-on:click.stop="previewMedia = item">
                                        <Eye class="w-4 h-4" :stroke-width="2" />
                                    </AppOverlayIconButton>
                                    <AppOverlayIconButton
                                        v-if="can('core.media.manage')"
                                        size="sm"
                                        variant="light"
                                        :title="t('shared.common.edit')"
                                        v-on:click.stop="openEditMedia(item)"
                                    >
                                        <Pencil class="w-4 h-4" :stroke-width="2" />
                                    </AppOverlayIconButton>
                                    <AppOverlayIconButton size="sm" variant="light" :title="t('backend.media.copyUrl')" v-on:click.stop="copyUrl(item)">
                                        <Copy class="w-4 h-4" :stroke-width="2" />
                                    </AppOverlayIconButton>
                                    <AppOverlayIconButton size="sm" variant="light" :title="t('backend.media.qrCode')" v-on:click.stop="openQr(item)">
                                        <QrCode class="w-4 h-4" :stroke-width="2" />
                                    </AppOverlayIconButton>
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

                    <div v-else-if="displayedMedia.length" class="border border-line/60 rounded-xl overflow-hidden">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-surface-2/50 border-b border-line/40">
                                    <th v-if="isSelecting" class="w-8 px-3 py-2" />
                                    <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("backend.media.filename") }}</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden sm:table-cell">{{ t("backend.media.mimeType") }}</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("backend.media.size") }}</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted w-24">{{ t("shared.common.actions") }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/40">
                                <tr
                                    v-for="item in displayedMedia"
                                    :key="item.id"
                                    class="group hover:bg-surface-2/40 transition-colors cursor-pointer"
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
                                                v-if="item.isImage"
                                                :src="item.thumbnailUrl ?? item.url"
                                                :alt="item.alt ?? ''"
                                                object-fit="cover"
                                                rounded="rounded"
                                                class="w-8 h-8 shrink-0"
                                            />
                                            <div v-else-if="item.isPdf" class="w-8 h-8 shrink-0 flex items-center justify-center bg-red-500/10 rounded">
                                                <FileText class="w-4 h-4 text-red-400" :stroke-width="1.5" />
                                            </div>
                                            <div v-else class="w-8 h-8 shrink-0 flex items-center justify-center bg-surface-2 rounded">
                                                <Film class="w-4 h-4 text-muted" :stroke-width="1.5" />
                                            </div>
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
                                        <div class="flex justify-end gap-0.5" v-on:click.stop>
                                            <AppIconButton :title="t('backend.media.preview')" v-on:click="previewMedia = item"><Eye class="w-3.5 h-3.5" :stroke-width="2" /></AppIconButton>
                                            <AppIconButton v-if="can('core.media.manage')" color="accent" :title="t('shared.common.edit')" v-on:click="openEditMedia(item)"><Pencil class="w-3.5 h-3.5" :stroke-width="2" /></AppIconButton>
                                            <AppIconButton :title="t('backend.media.copyUrl')" v-on:click="copyUrl(item)"><Copy class="w-3.5 h-3.5" :stroke-width="2" /></AppIconButton>
                                            <AppIconButton :title="t('backend.media.qrCode')" v-on:click="openQr(item)"><QrCode class="w-3.5 h-3.5" :stroke-width="2" /></AppIconButton>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </main>
        </div>

        <AppModal :show="!!editingMedia" max-width="3xl" :closeable="false" v-on:close="closeEditMedia">
            <div class="flex items-center justify-between gap-4 mb-1">
                <h3 class="text-lg font-semibold text-primary truncate">{{ t("backend.media.editMedia") }}</h3>
                <div class="flex border border-line/60 rounded-lg p-0.5 shrink-0">
                    <button type="button" class="px-3 py-1 rounded text-xs transition-colors" :class="editTab === 'edit' ? 'bg-surface-3 text-primary' : 'text-muted hover:text-primary'" v-on:click="editTab = 'edit'">{{ t("backend.media.tabEdit") }}</button>
                    <button type="button" class="px-3 py-1 rounded text-xs transition-colors" :class="editTab === 'history' ? 'bg-surface-3 text-primary' : 'text-muted hover:text-primary'" v-on:click="openHistoryTab">{{ t("backend.media.tabHistory") }}</button>
                    <button type="button" class="px-3 py-1 rounded text-xs transition-colors" :class="editTab === 'usage' ? 'bg-surface-3 text-primary' : 'text-muted hover:text-primary'" v-on:click="editTab = 'usage'; if (!mediaUsage) loadUsage()">{{ t("backend.media.tabUsage") }}</button>
                </div>
            </div>

            <div v-if="editTab === 'usage'" class="space-y-3 min-h-32">
                <div v-if="!mediaUsage" class="text-center py-8 text-muted text-sm">{{ t("shared.common.loading") }}</div>
                <template v-else>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-surface-2 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-primary">{{ mediaUsage.total }}</div>
                            <div class="text-xs text-muted mt-1">{{ t("backend.media.usageTotal") }}</div>
                        </div>
                        <div class="bg-surface-2 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-primary">{{ mediaUsage.directCount }}</div>
                            <div class="text-xs text-muted mt-1">{{ t("backend.media.usageDirect") }}</div>
                        </div>
                        <div class="bg-surface-2 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-primary">{{ mediaUsage.contentCount }}</div>
                            <div class="text-xs text-muted mt-1">{{ t("backend.media.usageContent") }}</div>
                        </div>
                    </div>
                    <AppNoData v-if="mediaUsage.total === 0" :message="t('backend.media.usageNone')" />
                </template>
            </div>

            <div v-if="editTab === 'history'" class="space-y-2 min-h-32">
                <div v-if="historyLoading" class="text-center py-8 text-muted text-sm">{{ t("shared.common.loading") }}</div>
                <AppNoData v-else-if="!mediaHistory.length" :message="t('backend.media.noHistory')" />
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
                    <div v-else-if="editingMedia?.isVideo" class="bg-surface-2 rounded-md overflow-hidden">
                        <video
                            :src="editingMedia.url"
                            controls
                            class="w-full h-auto block"
                            preload="metadata"
                        />
                    </div>
                    <div v-else-if="editingMedia?.isPdf" class="bg-surface-2 rounded-md overflow-hidden" style="height: 360px">
                        <iframe
                            :src="editingMedia.url"
                            class="w-full h-full border-0"
                            :title="editingMedia.originalName"
                        />
                    </div>
                    <div v-else class="bg-surface-2 rounded-md p-8 flex flex-col items-center">
                        <Film class="w-16 h-16 text-muted" :stroke-width="1.5" />
                        <p class="mt-2 text-xs text-muted">{{ editingMedia?.mimeType }}</p>
                    </div>
                    <div v-if="editingMedia?.isImage" class="flex items-center justify-between text-xs text-muted">
                        <span>{{ t("backend.media.focalHint") }}</span>
                        <button
                            v-if="editForm.focalX !== null"
                            type="button"
                            class="text-accent-400 hover:underline"
                            v-on:click="resetFocalPoint"
                        >
                            {{ t("backend.media.resetFocal") }}
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <AppInput
                        v-model="editForm.alt"
                        :label="t('backend.media.alt')"
                        :error="editErrors.alt ?? ''"
                        :placeholder="t('backend.media.altPlaceholder')"
                    />
                    <AppTextarea
                        v-model="editForm.caption"
                        :label="t('backend.media.caption')"
                        :placeholder="t('backend.media.captionPlaceholder')"
                        :rows="3"
                    />
                    <AppMultiselect
                        v-model="editForm.folderId"
                        :options="folderEditOptions"
                        :label="t('backend.media.folder')"
                        :placeholder="t('backend.media.rootFolder')"
                        :allow-empty="true"
                        track-by="id"
                        option-label="displayLabel"
                    />

                    <slot name="extra-form-fields" :form="editForm" :errors="editErrors" :media="editingMedia" />

                    <dl class="text-xs text-muted space-y-0.5 pt-2 border-t border-line">
                        <div class="flex justify-between"><dt>ID</dt><dd class="font-mono select-all">{{ editingMedia?.id }}</dd></div>
                        <div class="flex justify-between"><dt>{{ t("backend.media.filename") }}</dt><dd class="font-mono">{{ editingMedia?.originalName }}</dd></div>
                        <div class="flex justify-between"><dt>{{ t("backend.media.size") }}</dt><dd>{{ formatSize(editingMedia?.size ?? 0) }}</dd></div>
                        <div v-if="editingMedia?.width" class="flex justify-between"><dt>{{ t("backend.media.dimensions") }}</dt><dd>{{ editingMedia.width }}×{{ editingMedia.height }}</dd></div>
                        <div class="flex justify-between"><dt>{{ t("backend.media.mimeType") }}</dt><dd>{{ editingMedia?.mimeType }}</dd></div>
                        <div v-if="editingMedia?.uploadedBy" class="flex justify-between"><dt>{{ t("backend.media.uploadedBy") }}</dt><dd>{{ editingMedia.uploadedBy }}</dd></div>
                        <div v-if="editingMedia?.createdAt" class="flex justify-between"><dt>{{ t("backend.media.createdAt") }}</dt><dd>{{ formatDateTime(editingMedia.createdAt) }}</dd></div>
                        <div v-if="editingMedia?.updatedAt" class="flex justify-between"><dt>{{ t("backend.media.updatedAt") }}</dt><dd>{{ formatDateTime(editingMedia.updatedAt) }}</dd></div>
                        <div v-if="editingMedia?.permalink" class="flex justify-between items-center gap-2 pt-1 border-t border-line/40">
                            <dt class="shrink-0">{{ t("backend.media.permalink") }}</dt>
                            <dd class="font-mono text-xs text-accent-400 truncate cursor-pointer hover:underline" :title="editingMedia.permalink" v-on:click="copyUrl(editingMedia)">{{ editingMedia.permalink }}</dd>
                        </div>
                    </dl>
                </div>
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton v-if="editingMedia?.isImage" variant="secondary" size="md" v-on:click="openCrop(editingMedia)">
                        <Crop class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.media.crop") }}
                    </AppButton>
                    <AppButton variant="ghost" size="md" v-on:click="openQr(editingMedia)">
                        <QrCode class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.media.qrCode") }}
                    </AppButton>
                    <AppButton v-if="can('core.media.manage')" variant="danger" size="md" v-on:click="askDeleteMedia(editingMedia)">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}
                    </AppButton>
                    <AppButton variant="ghost" size="md" v-on:click="closeEditMedia"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" :loading="editSaving" v-on:click="submitMediaEdit">
                        <Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="folderModal.open"
            max-width="md"
            :title="folderModal.editing ? t('backend.media.editFolder') : t('backend.media.createFolder')"
            :icon="folderModal.editing ? Pencil : Folder"
            :closeable="false"
            v-on:close="folderModal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitFolder">
                <AppInput
                    v-model="folderForm.name"
                    :label="t('backend.media.folderName')"
                    :error="folderModal.errors.name ?? ''"
                />
                <AppMultiselect
                    v-model="folderForm.parentId"
                    :options="folderParentSelectOptions"
                    :label="t('backend.media.parentFolder')"
                    :placeholder="t('backend.media.rootFolder')"
                    :allow-empty="true"
                    track-by="id"
                    option-label="displayLabel"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="folderModal.open = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" :loading="folderModal.saving" v-on:click="submitFolder"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="pendingBulkDelete"
            max-width="sm"
            :title="t('backend.media.bulkDeleteConfirm', { count: selectedIds.size })"
            :icon="Trash2"
            :closeable="false"
            v-on:close="pendingBulkDelete = false"
        >
            <p class="text-sm text-secondary">{{ t("backend.media.bulkDeleteConfirmDesc") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingBulkDelete = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="bulkDeleteLoading" v-on:click="doBulkDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!deletingMedia"
            max-width="md"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="deletingMedia = null"
        >
            <p class="text-sm text-primary mb-3">{{ t("backend.media.deleteConfirm", { name: deletingMedia?.originalName }) }}</p>

            <div v-if="deletingMediaUsageLoading" class="text-xs text-muted italic">{{ t("backend.media.checkingUsage") }}</div>

            <div v-else-if="deletingMediaUsage && deletingMediaUsage.total > 0" class="rounded-lg border border-amber-500/40 bg-amber-500/5 p-3 space-y-2 mb-2">
                <p class="text-sm font-medium text-amber-600 dark:text-amber-400">
                    {{ t("backend.media.usageWarning", { count: deletingMediaUsage.total }) }}
                </p>
                <div v-for="group in deletingMediaUsage.groups" :key="group.type" class="text-xs text-secondary">
                    <p class="font-semibold uppercase tracking-wide text-[10px] text-muted mb-0.5">{{ t(`backend.media.usageGroups.${group.type}`) }}</p>
                    <ul class="space-y-0.5 ml-1">
                        <li v-for="(usage, idx) in group.items" :key="idx" class="flex items-center gap-1.5">
                            <span class="w-1 h-1 bg-current rounded-full opacity-50" />
                            <a
                                v-if="usage.href"
                                :href="usage.href"
                                target="_blank"
                                rel="noopener"
                                class="text-accent-400 hover:underline truncate"
                            >
                                {{ usage.label }}
                            </a>
                            <span v-else class="text-primary truncate">{{ usage.label }}</span>
                            <span v-if="usage.detail" class="text-muted">— {{ usage.detail }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <p v-else-if="deletingMediaUsage" class="flex items-center gap-1.5 text-xs text-emerald-500 italic">
                <CheckSquare class="w-3.5 h-3.5" :stroke-width="1.5" />
                {{ t("backend.media.usageNone") }}
            </p>

            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="deletingMedia = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="confirmDeleteMedia">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ deletingMediaUsage && deletingMediaUsage.total > 0 ? t("backend.media.deleteAnyway") : t("shared.common.delete") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!deletingFolder"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="deletingFolder = null"
        >
            <p class="text-sm text-primary">{{ t("backend.media.deleteFolderConfirm", { name: deletingFolder?.name }) }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="deletingFolder = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="confirmDeleteFolder"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal :show="!!previewMedia" max-width="4xl" :closeable="false" v-on:close="previewMedia = null">
            <div class="flex items-start justify-between gap-4 mb-3">
                <h3 class="text-sm font-medium text-primary truncate">{{ previewMedia?.originalName }}</h3>
                <div class="flex gap-2 shrink-0">
                    <AppButton size="sm" variant="ghost" v-on:click="copyUrl(previewMedia)">
                        <Copy class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.media.copyUrl") }}
                    </AppButton>
                    <AppButton size="sm" variant="ghost" v-on:click="openQr(previewMedia)">
                        <QrCode class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.media.qrCode") }}
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
                <video
                    v-else-if="previewMedia?.isVideo"
                    :src="previewMedia.url"
                    controls
                    class="max-w-full max-h-[70vh] rounded-lg"
                    preload="metadata"
                />
                <iframe
                    v-else-if="previewMedia?.isPdf"
                    :src="previewMedia.url"
                    class="w-full rounded-lg border-0"
                    style="height: 70vh"
                    :title="previewMedia.originalName"
                />
                <div v-else class="flex flex-col items-center gap-3 py-12">
                    <Film class="w-16 h-16 text-muted" :stroke-width="1.5" />
                    <p class="text-sm text-muted">{{ previewMedia?.mimeType }}</p>
                    <a :href="previewMedia?.url" target="_blank" class="text-sm text-accent-400 hover:underline">{{ t("backend.media.open") }}</a>
                </div>
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted">
                <div class="flex justify-between"><dt>{{ t("backend.media.size") }}</dt><dd>{{ formatSize(previewMedia?.size ?? 0) }}</dd></div>
                <div v-if="previewMedia?.width" class="flex justify-between"><dt>{{ t("backend.media.dimensions") }}</dt><dd>{{ previewMedia.width }}×{{ previewMedia.height }}</dd></div>
            </dl>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="previewMedia = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.close") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <MediaQrModal :media="qrMedia" :closeable="false" v-on:close="qrMedia = null" />

        <AppModal :show="openBulkMove" max-width="sm" v-on:close="openBulkMove = false">
            <h3 class="text-sm font-semibold text-primary mb-3">{{ t("backend.media.bulkMove", { count: selectedIds.size }) }}</h3>
            <AppMultiselect
                v-model="bulkMoveTargetId"
                :options="[{ id: null, displayLabel: t('backend.media.rootFolder') }, ...allFlatFolders.map(f => ({ id: f.id, displayLabel: '  '.repeat(f.depth) + f.name }))]"
                :label="t('backend.media.folder')"
                :placeholder="t('backend.media.rootFolder')"
                :allow-empty="true"
                track-by="id"
                option-label="displayLabel"
            />
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="openBulkMove = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" v-on:click="bulkMove(); openBulkMove = false"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <MediaCropperModal
            :media="cropMedia"
            :crop-path="cropPath"
            v-on:close="cropMedia = null"
            v-on:cropped="onCropped"
        />
    </div>
</template>
