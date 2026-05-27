<script setup>
import { ref, toRef } from "vue";
import { useI18n } from "vue-i18n";
import { useMediaPickerData }      from "@media/backend/media/composables/useMediaPickerData.js";
import { useMediaPickerSelection } from "@media/backend/media/composables/useMediaPickerSelection.js";
import { useMediaPickerUpload }    from "@media/backend/media/composables/useMediaPickerUpload.js";
import { useMediaPickerEdit }      from "@media/backend/media/composables/useMediaPickerEdit.js";
import {
    Folder,
    FolderOpen,
    Home,
    Layers,
    ChevronRight,
    ChevronDown,
    Image as ImageIcon,
    Film,
    Play,
    FileText,
    Files,
    X,
    Loader2,
    ExternalLink,
    Upload,
    Check,
    ArrowLeft,
    Menu,
} from "lucide-vue-next";
import { toast } from "vue-sonner";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppImage from "@/shared/components/display/AppImage.vue";
import AppSelectionCheck from "@/shared/components/feedback/AppSelectionCheck.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import PdfThumbnail from "@media/backend/media/components/PdfThumbnail.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppFileInput from "@/shared/components/form/file/AppFileInput.vue";
import AppTextarea from "@/shared/components/form/input/AppTextarea.vue";
import AppLink from "@/shared/components/nav/AppLink.vue";
import { useFileSize } from "@/shared/composables/format/useFileSize.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildFolderTree, flattenFolders } from "@media/backend/media/utils/folderTree.js";

const { t } = useI18n();
const { formatSize } = useFileSize();
const { formatDateTime } = useDateFormat();

const props = defineProps({
    show:       { type: Boolean, default: false },
    imagesOnly: { type: Boolean, default: false },
    multiple:   { type: Boolean, default: false },
    listPath:   { type: String,  default: "/backend/media/media/list" },
    uploadPath: { type: String,  default: "/backend/media/media/upload" },
    updatePath: { type: String,  default: "/backend/media/media/__id__/update" },
});

const emit           = defineEmits(["close", "select"]);
const searchInputRef = ref(null);
const foldersOpenMobile = ref(false);

// selected is shared between data (reset after reload) and selection (pick/isSelected)
const selected = ref(null);

const { items, folders, loading, search, currentFolderId, allMediaView, typeFilter, FILTERS, folderTree, collapsed, flatFolders, visibleItems, childFolders, currentFolderName, load, selectFolder, selectAllMedia, selectRoot, toggleCollapse } =
    useMediaPickerData({ listPath: props.listPath, show: toRef(props, 'show'), imagesOnly: props.imagesOnly, searchInputRef, selected });

const { multiSelected, isSelected, pick, confirm, close, pickFolderMobile } =
    useMediaPickerSelection({ show: props.show, multiple: props.multiple, emit, selectFolder, foldersOpenMobile, selected });

const { fileInputRef, uploading, dragOver, uploadFiles, onDragOver, onDragLeave, onDrop } =
    useMediaPickerUpload({ uploadPath: props.uploadPath, imagesOnly: props.imagesOnly, items, currentFolderId, selected });

const { editAlt, editCaption, editSaving, editSaved, saveEdit } =
    useMediaPickerEdit({ updatePath: props.updatePath, items, selected });

function typeIcon(item) {
    if (item.isImage) return ImageIcon;
    if (item.mimeType?.startsWith("video/")) return Film;
    return FileText;
}

function dimensions(item) {
    if (!item.width || !item.height) return null;
    return `${item.width} × ${item.height}`;
}
</script>

<template>
    <AppModal
        :show="show"
        max-width="6xl"
        no-padding
        :closeable="false"
        v-on:close="close"
    >
        <div class="flex flex-col h-dvh sm:h-[80vh] sm:max-h-[80vh]">
            <header class="flex items-center gap-2 px-4 sm:px-5 py-3 border-b border-line shrink-0">
                <AppIconButton
                    class="md:hidden -ml-1"
                    :title="t('shared.media.picker.allFolders')"
                    v-on:click="foldersOpenMobile = true"
                >
                    <Menu class="w-5 h-5" :stroke-width="2" />
                </AppIconButton>
                <h2 class="text-base font-semibold text-primary truncate flex-1">{{ t("shared.media.picker.title") }}</h2>
            </header>

            <div class="flex flex-1 min-h-0 relative">
                <!-- Folders sidemenu (drawer on mobile) -->
                <aside
                    class="border-r border-line overflow-y-auto scrollbar-thin shrink-0
                           md:static md:w-56 md:translate-x-0 md:bg-surface-2/40
                           fixed inset-y-0 left-0 z-30 w-72 bg-surface transition-transform duration-200 ease-out"
                    :class="foldersOpenMobile ? 'translate-x-0 shadow-2xl' : '-translate-x-full md:translate-x-0'"
                >
                    <div class="md:hidden flex items-center gap-2 px-3 py-3 border-b border-line">
                        <span class="font-medium text-sm text-primary flex-1">{{ t("shared.media.picker.allFolders") }}</span>
                        <AppIconButton :title="t('shared.common.close')" v-on:click="foldersOpenMobile = false">
                            <X class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                    <button
                        type="button"
                        class="w-full flex items-center gap-2 px-3 py-2 text-sm transition-colors"
                        :class="allMediaView ? 'bg-accent-600/15 text-accent-400' : 'text-secondary hover:bg-surface-2 hover:text-primary'"
                        v-on:click="selectAllMedia(); foldersOpenMobile = false"
                    >
                        <Layers class="w-4 h-4 shrink-0" :stroke-width="2" />
                        <span class="truncate">{{ t("shared.media.picker.allFolders") }}</span>
                    </button>
                    <button
                        type="button"
                        class="w-full flex items-center gap-2 px-3 py-2 text-sm transition-colors"
                        :class="!allMediaView && !currentFolderId ? 'bg-accent-600/15 text-accent-400' : 'text-secondary hover:bg-surface-2 hover:text-primary'"
                        v-on:click="selectRoot(); foldersOpenMobile = false"
                    >
                        <Home class="w-4 h-4 shrink-0" :stroke-width="2" />
                        <span class="truncate">{{ t("shared.media.picker.rootFolder") }}</span>
                    </button>
                    <div v-if="flatFolders.length" class="border-t border-line/60 mt-1 pt-1">
                        <div
                            v-for="folder in flatFolders"
                            :key="folder.id"
                            class="group flex items-stretch text-sm transition-colors"
                            :class="currentFolderId === folder.id ? 'bg-accent-600/15 text-accent-400' : 'text-secondary hover:bg-surface-2 hover:text-primary'"
                        >
                            <button
                                v-if="folder.children?.length"
                                type="button"
                                class="px-1 shrink-0 text-muted hover:text-primary"
                                :title="collapsed.has(folder.id) ? t('shared.common.expand') : t('shared.common.collapse')"
                                v-on:click.stop="toggleCollapse(folder.id)"
                            >
                                <ChevronDown v-if="!collapsed.has(folder.id)" class="w-3.5 h-3.5" :stroke-width="2" />
                                <ChevronRight v-else class="w-3.5 h-3.5" :stroke-width="2" />
                            </button>
                            <span v-else class="w-5 shrink-0" />
                            <button
                                type="button"
                                class="flex-1 flex items-center gap-2 py-1.5 pr-2 text-left min-w-0"
                                :style="{ paddingLeft: `${folder.depth * 0.75}rem` }"
                                v-on:click="pickFolderMobile(folder.id)"
                            >
                                <FolderOpen v-if="currentFolderId === folder.id" class="w-4 h-4 shrink-0" :stroke-width="2" />
                                <Folder v-else class="w-4 h-4 shrink-0" :stroke-width="2" />
                                <span class="truncate">{{ folder.name }}</span>
                                <span v-if="folder.mediaCount" class="ml-auto text-xs text-muted">{{ folder.mediaCount }}</span>
                            </button>
                        </div>
                    </div>
                </aside>

                <!-- Backdrop for mobile drawer -->
                <div
                    v-if="foldersOpenMobile"
                    class="md:hidden fixed inset-0 z-20 bg-black/40"
                    v-on:click="foldersOpenMobile = false"
                />

                <!-- Main area -->
                <div class="flex-1 flex flex-col min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 px-3 sm:px-4 py-3 border-b border-line shrink-0 sm:flex-wrap">
                        <div class="flex items-center gap-2">
                            <AppSearchInput
                                ref="searchInputRef"
                                v-model="search"
                                :placeholder="t('shared.media.picker.search')"
                                :debounce="250"
                                class="flex-1 sm:min-w-48"
                                v-on:search="load"
                            />
                            <AppFileInput
                                ref="fileInputRef"
                                :multiple="true"
                                :accept="imagesOnly ? 'image/*' : '*'"
                                v-on:change="uploadFiles"
                            />
                            <AppButton
                                variant="primary"
                                size="sm"
                                :loading="uploading"
                                class="shrink-0 sm:hidden"
                                v-on:click="fileInputRef?.trigger()"
                            >
                                <Upload class="w-3.5 h-3.5" :stroke-width="2" />
                            </AppButton>
                        </div>
                        <div class="flex items-center gap-1 overflow-x-auto scrollbar-hide -mx-1 px-1">
                            <AppTab
                                v-for="f in FILTERS"
                                :key="f.key"
                                :active="typeFilter === f.key"
                                size="sm"
                                class="shrink-0"
                                v-on:click="typeFilter = f.key"
                            >
                                <component :is="f.icon" class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ f.label }}
                            </AppTab>
                        </div>
                        <div v-if="loading" class="text-xs text-muted flex items-center gap-1.5">
                            <Loader2 class="w-3.5 h-3.5 animate-spin" :stroke-width="2" />
                            {{ t("shared.common.loading") }}
                        </div>
                        <AppButton
                            variant="primary"
                            size="sm"
                            :loading="uploading"
                            class="hidden sm:inline-flex sm:ml-auto"
                            v-on:click="fileInputRef?.trigger()"
                        >
                            <Upload class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("shared.media.picker.upload") }}
                        </AppButton>
                    </div>

                    <div v-if="currentFolderName || (search && search.trim())" class="px-4 py-2 border-b border-line shrink-0 flex items-center gap-2 text-xs text-muted">
                        <span v-if="currentFolderName">{{ t("shared.media.picker.in") }} <strong class="text-primary">{{ currentFolderName }}</strong></span>
                        <span v-if="search && search.trim()">· « {{ search }} »</span>
                        <span class="ml-auto">{{ visibleItems.length }} {{ t("shared.media.picker.items") }}</span>
                    </div>

                    <div
                        class="flex-1 overflow-y-auto scrollbar-thin p-4 relative"
                        v-on:dragover="onDragOver"
                        v-on:dragleave="onDragLeave"
                        v-on:drop="onDrop"
                    >
                        <div
                            v-if="dragOver"
                            class="absolute inset-2 z-10 rounded-xl border-2 border-dashed border-accent-500 bg-accent-500/10 flex items-center justify-center pointer-events-none"
                        >
                            <div class="flex flex-col items-center gap-2 text-accent-400">
                                <Upload class="w-8 h-8" :stroke-width="1.5" />
                                <span class="text-sm font-medium">{{ t("shared.media.picker.dropHere") }}</span>
                            </div>
                        </div>
                        <div v-if="childFolders.length" class="mb-4">
                            <p class="text-xs text-muted uppercase tracking-wide mb-2 px-1">{{ t("shared.media.picker.subfolders") }}</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                <button
                                    v-for="folder in childFolders"
                                    :key="`folder-${folder.id}`"
                                    type="button"
                                    class="group relative flex items-center gap-2 rounded-lg border border-line bg-surface-2 hover:border-accent-400 hover:bg-surface-2/80 transition-colors px-3 py-2.5 text-left"
                                    v-on:click="selectFolder(folder.id)"
                                    v-on:dblclick="selectFolder(folder.id)"
                                >
                                    <Folder class="w-5 h-5 text-accent-400 shrink-0" :stroke-width="2" />
                                    <span class="flex-1 text-sm text-primary truncate">{{ folder.name }}</span>
                                    <span v-if="folder.mediaCount" class="text-xs text-muted shrink-0">{{ folder.mediaCount }}</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="!loading && !visibleItems.length && !childFolders.length" class="text-center py-12 text-sm text-muted">
                            {{ t("shared.media.picker.empty") }}
                        </div>
                        <div v-else-if="visibleItems.length">
                            <p v-if="childFolders.length" class="text-xs text-muted uppercase tracking-wide mb-2 px-1">{{ t("shared.media.picker.files") }}</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                <button
                                    v-for="item in visibleItems"
                                    :key="item.id"
                                    type="button"
                                    class="group relative aspect-square rounded-lg overflow-hidden border-2 transition-all bg-surface-2"
                                    :class="isSelected(item) ? 'border-accent-500 ring-2 ring-accent-500/30' : 'border-line hover:border-accent-400'"
                                    v-on:click="pick(item)"
                                    v-on:dblclick="multiple ? null : (pick(item), confirm())"
                                >
                                    <AppImage
                                        v-if="item.isImage"
                                        :src="item.thumbnailUrl ?? item.url"
                                        :alt="item.alt ?? item.originalName ?? ''"
                                        object-fit="cover"
                                        class="w-full h-full"
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
                                                <Play class="w-5 h-5 text-white fill-white" :stroke-width="0" />
                                            </div>
                                        </div>
                                    </template>
                                    <div v-else-if="item.isPdf" class="relative w-full h-full pointer-events-none">
                                        <PdfThumbnail :url="item.url" />
                                    </div>
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center text-muted gap-2 p-2">
                                        <component :is="typeIcon(item)" class="w-8 h-8" :stroke-width="1.5" />
                                        <span class="text-[10px] font-mono uppercase tracking-wide">{{ item.mimeType?.split("/")?.[1] ?? "" }}</span>
                                    </div>
                                    <AppSelectionCheck v-if="multiple" :active="isSelected(item)" class="absolute top-1.5 left-1.5" />
                                    <div class="absolute inset-x-0 bottom-0 px-2 py-1 bg-linear-to-t from-black/80 to-transparent text-white text-xs truncate text-left">
                                        {{ item.originalName }}
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details panel: sidemenu on desktop, fullscreen overlay on mobile -->
                <aside
                    v-if="selected"
                    class="border-l border-line overflow-y-auto scrollbar-thin shrink-0
                           md:static md:w-72 md:bg-surface-2/40
                           absolute inset-0 z-20 w-full bg-surface"
                >
                    <div class="md:hidden flex items-center gap-2 px-3 py-3 border-b border-line sticky top-0 bg-surface z-10">
                        <AppIconButton class="-ml-1" :title="t('shared.common.back')" v-on:click="selected = null">
                            <ArrowLeft class="w-5 h-5" :stroke-width="2" />
                        </AppIconButton>
                        <span class="font-medium text-sm text-primary truncate flex-1">{{ selected.originalName }}</span>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="aspect-square rounded-lg overflow-hidden border border-line bg-surface flex items-center justify-center">
                            <AppImage
                                v-if="selected.isImage"
                                :src="selected.thumbnailUrl ?? selected.url"
                                :alt="selected.alt ?? selected.originalName ?? ''"
                                object-fit="contain"
                                class="w-full h-full"
                            />
                            <video
                                v-else-if="selected.isVideo"
                                :src="selected.url"
                                controls
                                class="w-full h-full object-contain"
                                preload="metadata"
                            />
                            <div v-else-if="selected.isPdf" class="relative w-full h-full">
                                <PdfThumbnail :url="selected.url" />
                            </div>
                            <component :is="typeIcon(selected)" v-else class="w-12 h-12 text-muted" :stroke-width="1.5" />
                        </div>

                        <div>
                            <p class="text-sm font-medium text-primary break-all">{{ selected.originalName }}</p>
                            <p class="text-xs text-muted font-mono mt-0.5">{{ selected.mimeType }}</p>
                        </div>

                        <dl class="space-y-2 text-xs">
                            <div v-if="dimensions(selected)" class="flex justify-between gap-2">
                                <dt class="text-muted">{{ t("shared.media.picker.dimensions") }}</dt>
                                <dd class="text-primary font-mono">{{ dimensions(selected) }}</dd>
                            </div>
                            <div v-if="selected.size" class="flex justify-between gap-2">
                                <dt class="text-muted">{{ t("shared.media.picker.size") }}</dt>
                                <dd class="text-primary font-mono">{{ formatSize(selected.size) }}</dd>
                            </div>
                            <div v-if="selected.createdAt" class="flex justify-between gap-2">
                                <dt class="text-muted">{{ t("shared.media.picker.uploaded") }}</dt>
                                <dd class="text-primary">{{ formatDateTime(selected.createdAt) }}</dd>
                            </div>
                            <div v-if="selected.folderName" class="flex justify-between gap-2">
                                <dt class="text-muted">{{ t("shared.media.picker.folder") }}</dt>
                                <dd class="text-primary truncate">{{ selected.folderName }}</dd>
                            </div>
                        </dl>

                        <div class="space-y-3 pt-3 border-t border-line">
                            <AppInput
                                v-model="editAlt"
                                :label="t('shared.media.picker.alt')"
                                :placeholder="t('shared.media.picker.altPlaceholder')"
                                :disabled="editSaving"
                                v-on:blur="saveEdit"
                            />
                            <AppTextarea
                                v-model="editCaption"
                                :label="t('shared.media.picker.caption')"
                                :placeholder="t('shared.media.picker.captionPlaceholder')"
                                :rows="2"
                                :disabled="editSaving"
                                v-on:blur="saveEdit"
                            />
                            <div v-if="editSaving" class="text-xs text-muted flex items-center gap-1.5">
                                <Loader2 class="w-3 h-3 animate-spin" :stroke-width="2" />
                                {{ t("shared.common.saving") }}
                            </div>
                            <div v-else-if="editSaved" class="text-xs text-emerald-500 flex items-center gap-1">
                                <Check class="w-3 h-3" :stroke-width="2.5" />
                                {{ t("shared.common.saved") }}
                            </div>
                        </div>

                        <AppLink
                            :href="`/backend/media/media?focus=${selected.id}`"
                            target="_blank"
                            variant="front"
                            class="inline-flex items-center gap-1.5 text-xs"
                        >
                            <ExternalLink class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("shared.media.picker.openInLibrary") }}
                        </AppLink>
                    </div>
                </aside>
            </div>

            <footer class="flex items-center justify-end gap-2 px-4 sm:px-5 py-3 border-t border-line shrink-0">
                <span v-if="multiple" class="text-xs text-muted mr-auto">{{ multiSelected.length }} {{ t("shared.media.picker.selectedCount") }}</span>
                <AppButton variant="ghost" size="md" class="flex-1 sm:flex-none" v-on:click="close"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                <AppButton
                    variant="primary"
                    size="md"
                    :disabled="multiple ? multiSelected.length === 0 : !selected"
                    class="flex-1 sm:flex-none"
                    v-on:click="confirm"
                >
                    <Check class="w-3.5 h-3.5" :stroke-width="2" /> {{ multiple ? t("shared.media.picker.addSelected") : t("shared.media.picker.select") }}
                </AppButton>
            </footer>
        </div>
    </AppModal>
</template>
