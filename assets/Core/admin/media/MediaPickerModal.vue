<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import {
    Search,
    Folder,
    FolderOpen,
    Home,
    ChevronRight,
    ChevronDown,
    Image as ImageIcon,
    Film,
    FileText,
    Files,
    X,
    Loader2,
    ExternalLink,
    Upload,
    Check,
} from "lucide-vue-next";
import { toast } from "vue-sonner";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppImage from "@/shared/components/display/AppImage.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { useFileSize } from "@/shared/composables/format/useFileSize.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildFolderTree, flattenFolders } from "@core/admin/media/utils/folderTree.js";

const { t } = useI18n();
const { formatSize } = useFileSize();
const { formatDateTime } = useDateFormat();

const props = defineProps({
    show: { type: Boolean, default: false },
    imagesOnly: { type: Boolean, default: false },
    listPath: { type: String, default: "/admin/media/list" },
    uploadPath: { type: String, default: "/admin/media/upload" },
    editPath: { type: String, default: "/admin/media/__id__/edit" },
});

const emit = defineEmits(["close", "select"]);

// ── Data ─────────────────────────────────────────────────────────────────────
const items = ref([]);
const folders = ref([]);
const loading = ref(false);
const search = ref("");
const currentFolderId = ref(null);
const typeFilter = ref("all");
const selected = ref(null);
const searchInputRef = ref(null);
let abortCtrl = null;

const FILTERS = computed(() => {
    if (props.imagesOnly) return [{ key: "all", label: t("shared.media.filters.all"), icon: Files }];
    return [
        { key: "all", label: t("shared.media.filters.all"), icon: Files },
        { key: "image", label: t("shared.media.filters.image"), icon: ImageIcon },
        { key: "video", label: t("shared.media.filters.video"), icon: Film },
        { key: "document", label: t("shared.media.filters.document"), icon: FileText },
    ];
});

const folderTree = computed(() => buildFolderTree(folders.value));
const collapsed = ref(new Set());
const flatFolders = computed(() => flattenFolders(folderTree.value, 0, collapsed.value));

const visibleItems = computed(() => {
    return items.value.filter((m) => {
        if (props.imagesOnly && !m.isImage) return false;
        if (typeFilter.value === "image") return m.mimeType?.startsWith("image/");
        if (typeFilter.value === "video") return m.mimeType?.startsWith("video/");
        if (typeFilter.value === "document") return !m.mimeType?.startsWith("image/") && !m.mimeType?.startsWith("video/");
        return true;
    });
});

// ── Loading ──────────────────────────────────────────────────────────────────
async function load() {
    abortCtrl?.abort();
    abortCtrl = new AbortController();
    loading.value = true;
    try {
        const url = new URL(props.listPath, window.location.origin);
        if (search.value.trim()) url.searchParams.set("search", search.value.trim());
        if (currentFolderId.value) url.searchParams.set("folderId", String(currentFolderId.value));
        const response = await fetch(url, { signal: abortCtrl.signal, headers: { Accept: "application/json" } });
        if (!response.ok) throw new Error();
        const data = await response.json();
        items.value = data.items ?? [];
        folders.value = data.folders ?? folders.value;
        if (selected.value && !items.value.some((m) => m.id === selected.value.id)) {
            selected.value = null;
        }
    } catch (e) {
        if (e.name !== "AbortError") items.value = [];
    } finally {
        loading.value = false;
    }
}

const debouncedLoad = useDebounce(load, 250);
watch(search, debouncedLoad);
watch(currentFolderId, load);

watch(
    () => props.show,
    async (visible) => {
        if (visible) {
            await load();
            await nextTick();
            searchInputRef.value?.focus();
        } else {
            selected.value = null;
            search.value = "";
            typeFilter.value = "all";
        }
    },
    { immediate: true },
);

// ── Folder navigation ────────────────────────────────────────────────────────
function selectFolder(id) {
    currentFolderId.value = id;
}

function toggleCollapse(id) {
    const next = new Set(collapsed.value);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    collapsed.value = next;
}

const currentFolderName = computed(() => {
    if (!currentFolderId.value) return null;
    return folders.value.find((f) => f.id === currentFolderId.value)?.name ?? null;
});

// ── Selection ────────────────────────────────────────────────────────────────
function pick(item) {
    selected.value = item;
}

function confirm() {
    if (selected.value) emit("select", selected.value);
}

function close() {
    emit("close");
}

// ── Keyboard ─────────────────────────────────────────────────────────────────
function onKey(event) {
    if (!props.show) return;
    if (event.key === "Escape") {
        event.preventDefault();
        close();
        return;
    }
    if (event.key === "Enter" && selected.value && document.activeElement?.tagName !== "INPUT") {
        event.preventDefault();
        confirm();
    }
}

onMounted(() => document.addEventListener("keydown", onKey));
onBeforeUnmount(() => document.removeEventListener("keydown", onKey));

// ── Upload ───────────────────────────────────────────────────────────────────
const fileInputRef = ref(null);
const uploading = ref(false);
const dragOver = ref(false);

async function uploadFiles(files) {
    if (!files?.length) return;
    uploading.value = true;
    let lastUploaded = null;
    try {
        for (const file of files) {
            const body = new FormData();
            body.append("image", file);
            if (currentFolderId.value) body.append("folderId", String(currentFolderId.value));
            const response = await fetch(props.uploadPath, { method: HttpMethod.Post, body });
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (data.success && data.media) {
                items.value.unshift(data.media);
                lastUploaded = data.media;
            }
        }
        if (lastUploaded && (!props.imagesOnly || lastUploaded.isImage)) {
            selected.value = lastUploaded;
        }
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        uploading.value = false;
        if (fileInputRef.value) fileInputRef.value.value = "";
    }
}

function onPickFiles(event) {
    uploadFiles(Array.from(event.target.files ?? []));
}

function onDragOver(event) {
    if (!event.dataTransfer.types.includes("Files")) return;
    event.preventDefault();
    dragOver.value = true;
}

function onDragLeave(event) {
    if (!event.currentTarget.contains(event.relatedTarget)) {
        dragOver.value = false;
    }
}

function onDrop(event) {
    if (!event.dataTransfer.types.includes("Files")) return;
    event.preventDefault();
    dragOver.value = false;
    uploadFiles(Array.from(event.dataTransfer.files ?? []));
}

// ── Inline edit (alt + caption) ──────────────────────────────────────────────
const editAlt = ref("");
const editCaption = ref("");
const editSaving = ref(false);
const editSaved = ref(false);
let savedTimer = null;

watch(selected, (item) => {
    editAlt.value = item?.alt ?? "";
    editCaption.value = item?.caption ?? "";
    editSaved.value = false;
});

async function saveEdit() {
    const item = selected.value;
    if (!item) return;
    if (editAlt.value === (item.alt ?? "") && editCaption.value === (item.caption ?? "")) return;
    editSaving.value = true;
    try {
        const url = props.editPath.replace("__id__", item.id);
        const response = await fetch(url, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                alt: editAlt.value,
                caption: editCaption.value,
                focalX: item.focalX ?? null,
                focalY: item.focalY ?? null,
                folderId: item.folderId ?? null,
            }),
        });
        if (!response.ok) throw new Error();
        const data = await response.json();
        if (!data.success) throw new Error();
        const index = items.value.findIndex((m) => m.id === item.id);
        if (index !== -1) items.value[index] = data.media;
        selected.value = data.media;
        editSaved.value = true;
        if (savedTimer) clearTimeout(savedTimer);
        savedTimer = setTimeout(() => { editSaved.value = false; }, 2000);
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        editSaving.value = false;
    }
}

// ── Helpers ──────────────────────────────────────────────────────────────────
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
        scrollable
        v-on:close="close"
    >
        <div class="flex flex-col h-[80vh] max-h-[80vh]">
            <header class="flex items-center justify-between px-5 py-3 border-b border-line shrink-0">
                <h2 class="text-base font-semibold text-primary">{{ t("shared.media.picker.title") }}</h2>
                <button type="button" class="p-1 text-muted hover:text-primary rounded" v-on:click="close">
                    <X class="w-5 h-5" :stroke-width="2" />
                </button>
            </header>

            <div class="flex flex-1 min-h-0">
                <!-- Folders sidebar -->
                <aside class="w-56 shrink-0 border-r border-line bg-surface-2/40 overflow-y-auto scrollbar-thin">
                    <button
                        type="button"
                        class="w-full flex items-center gap-2 px-3 py-2 text-sm transition-colors"
                        :class="!currentFolderId ? 'bg-accent-600/15 text-accent-400' : 'text-secondary hover:bg-surface-2 hover:text-primary'"
                        v-on:click="selectFolder(null)"
                    >
                        <Home class="w-4 h-4 shrink-0" :stroke-width="2" />
                        <span class="truncate">{{ t("shared.media.picker.allFolders") }}</span>
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
                                v-on:click="selectFolder(folder.id)"
                            >
                                <FolderOpen v-if="currentFolderId === folder.id" class="w-4 h-4 shrink-0" :stroke-width="2" />
                                <Folder v-else class="w-4 h-4 shrink-0" :stroke-width="2" />
                                <span class="truncate">{{ folder.name }}</span>
                                <span v-if="folder.mediaCount" class="ml-auto text-xs text-muted">{{ folder.mediaCount }}</span>
                            </button>
                        </div>
                    </div>
                </aside>

                <!-- Main area -->
                <div class="flex-1 flex flex-col min-w-0">
                    <div class="flex items-center gap-3 px-4 py-3 border-b border-line shrink-0 flex-wrap">
                        <div class="relative flex-1 min-w-48">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" :stroke-width="2" />
                            <input
                                ref="searchInputRef"
                                v-model="search"
                                type="search"
                                :placeholder="t('shared.media.picker.search')"
                                class="w-full pl-9 pr-3 py-2 rounded-md border border-line bg-surface text-sm text-primary placeholder-muted outline-none focus:border-accent-500"
                            >
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                v-for="f in FILTERS"
                                :key="f.key"
                                type="button"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors"
                                :class="typeFilter === f.key ? 'bg-accent-600/15 text-accent-400' : 'text-secondary hover:bg-surface-2 hover:text-primary'"
                                v-on:click="typeFilter = f.key"
                            >
                                <component :is="f.icon" class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ f.label }}
                            </button>
                        </div>
                        <div v-if="loading" class="text-xs text-muted flex items-center gap-1.5">
                            <Loader2 class="w-3.5 h-3.5 animate-spin" :stroke-width="2" />
                            {{ t("shared.common.loading") }}
                        </div>
                        <input
                            ref="fileInputRef"
                            type="file"
                            multiple
                            :accept="imagesOnly ? 'image/*' : '*'"
                            class="hidden"
                            v-on:change="onPickFiles"
                        >
                        <AppButton
                            variant="primary"
                            size="sm"
                            :loading="uploading"
                            v-on:click="fileInputRef?.click()"
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
                        <div v-if="!loading && !visibleItems.length" class="text-center py-12 text-sm text-muted">
                            {{ t("shared.media.picker.empty") }}
                        </div>
                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            <button
                                v-for="item in visibleItems"
                                :key="item.id"
                                type="button"
                                class="group relative aspect-square rounded-lg overflow-hidden border-2 transition-all bg-surface-2"
                                :class="selected?.id === item.id ? 'border-accent-500 ring-2 ring-accent-500/30' : 'border-line hover:border-accent-400'"
                                v-on:click="pick(item)"
                                v-on:dblclick="pick(item); confirm()"
                            >
                                <AppImage
                                    v-if="item.isImage"
                                    :src="item.thumbnailUrl ?? item.url"
                                    :alt="item.alt ?? item.originalName ?? ''"
                                    object-fit="cover"
                                    class="w-full h-full"
                                />
                                <div v-else class="w-full h-full flex flex-col items-center justify-center text-muted gap-2 p-2">
                                    <component :is="typeIcon(item)" class="w-8 h-8" :stroke-width="1.5" />
                                    <span class="text-[10px] font-mono uppercase tracking-wide">{{ item.mimeType?.split("/")?.[1] ?? "" }}</span>
                                </div>
                                <div class="absolute inset-x-0 bottom-0 px-2 py-1 bg-gradient-to-t from-black/80 to-transparent text-white text-xs truncate text-left">
                                    {{ item.originalName }}
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Details panel -->
                <aside v-if="selected" class="w-72 shrink-0 border-l border-line bg-surface-2/40 overflow-y-auto scrollbar-thin">
                    <div class="p-4 space-y-4">
                        <div class="aspect-square rounded-lg overflow-hidden border border-line bg-surface flex items-center justify-center">
                            <AppImage
                                v-if="selected.isImage"
                                :src="selected.thumbnailUrl ?? selected.url"
                                :alt="selected.alt ?? selected.originalName ?? ''"
                                object-fit="contain"
                                class="w-full h-full"
                            />
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
                                :disabled="editSaving"
                                v-on:blur="saveEdit"
                            />
                            <AppTextarea
                                v-model="editCaption"
                                :label="t('shared.media.picker.caption')"
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

                        <a :href="`/admin/media?focus=${selected.id}`" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-accent-400 hover:underline">
                            <ExternalLink class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("shared.media.picker.openInLibrary") }}
                        </a>
                    </div>
                </aside>
            </div>

            <footer class="flex items-center justify-end gap-2 px-5 py-3 border-t border-line shrink-0">
                <AppButton variant="ghost" size="md" v-on:click="close">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="primary" size="md" :disabled="!selected" v-on:click="confirm">
                    {{ t("shared.media.picker.select") }}
                </AppButton>
            </footer>
        </div>
    </AppModal>
</template>
