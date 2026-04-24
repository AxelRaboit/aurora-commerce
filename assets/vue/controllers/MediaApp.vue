<script setup>
import { ref, reactive, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { Pencil, Trash2, Folder, FolderPlus, Upload, Search, Image as ImageIcon, ChevronRight, Home } from "lucide-vue-next";
import AppButton from "@/components/AppButton.vue";
import AppIconButton from "@/components/AppIconButton.vue";
import AppInput from "@/components/AppInput.vue";
import AppTextarea from "@/components/AppTextarea.vue";
import AppSelect from "@/components/AppSelect.vue";
import AppModal from "@/components/AppModal.vue";
import AppMessage from "@/components/AppMessage.vue";
import AppNoData from "@/components/AppNoData.vue";
import { useFileSize } from "@/composables/useFileSize.js";

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
    folderCreatePath: { type: String, default: "/admin/media/folders" },
    folderEditPath: { type: String, default: "/admin/media/folders/__id__/edit" },
    folderDeletePath: { type: String, default: "/admin/media/folders/__id__/delete" },
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

function flattenFolders(nodes, depth = 0) {
    const result = [];
    for (const node of nodes) {
        result.push({ ...node, depth });
        if (node.children.length) result.push(...flattenFolders(node.children, depth + 1));
    }
    return result;
}

const folderTree = computed(() => buildFolderTree(folders.value));
const flatFolders = computed(() => flattenFolders(folderTree.value));

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

async function uploadFiles(event) {
    const files = Array.from(event.target.files ?? []);
    if (!files.length) return;
    uploading.value = true;
    try {
        for (const file of files) {
            const body = new FormData();
            body.append("image", file);
            if (currentFolderId.value) body.append("folderId", String(currentFolderId.value));
            const response = await fetch(props.uploadPath, { method: "POST", body });
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (data.media) media.value.unshift(data.media);
        }
        toast.success(t("admin.media.uploaded", { count: files.length }));
    } catch {
        toast.error(t("common.error"));
    } finally {
        uploading.value = false;
        if (uploadInput.value) uploadInput.value.value = "";
    }
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
            method: "POST",
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
        toast.success(t("common.saved"));
        closeEditMedia();
    } catch {
        toast.error(t("common.error"));
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
        const response = await fetch(props.deletePath.replace("__id__", item.id), { method: "POST" });
        const data = await response.json();
        if (!data.success) {
            toast.error(t("common.error"));
            return;
        }
        media.value = media.value.filter((m) => m.id !== item.id);
        toast.success(t("common.deleted"));
    } catch {
        toast.error(t("common.error"));
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
            method: "POST",
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
        toast.success(t("common.saved"));
        folderModal.open = false;
    } catch {
        toast.error(t("common.error"));
    } finally {
        folderModal.saving = false;
    }
}

const deletingFolder = ref(null);

async function confirmDeleteFolder() {
    const folder = deletingFolder.value;
    if (!folder) return;
    try {
        const response = await fetch(props.folderDeletePath.replace("__id__", folder.id), { method: "POST" });
        const data = await response.json();
        if (!data.success) {
            toast.error(t("common.error"));
            return;
        }
        folders.value = folders.value.filter((f) => f.id !== folder.id);
        if (currentFolderId.value === folder.id) {
            navigateTo(folder.parentId ?? null);
            return;
        }
        toast.success(t("common.deleted"));
    } catch {
        toast.error(t("common.error"));
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
    return flatFolders.value.filter((f) => !forbidden.has(f.id));
});
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-4 min-h-[calc(100vh-8rem)]">
        <!-- Folder sidebar -->
        <aside class="lg:w-72 shrink-0 space-y-2">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("admin.media.folders") }}</h2>
                <AppButton variant="primary" size="sm" v-on:click="openCreateFolder">
                    <FolderPlus class="w-3.5 h-3.5" :stroke-width="2" />
                </AppButton>
            </div>
            <div class="space-y-0.5">
                <button
                    type="button"
                    class="w-full text-left px-3 py-2 rounded-lg transition-colors flex items-center gap-2"
                    :class="!currentFolderId
                        ? 'bg-indigo-600/15 text-indigo-400 border border-indigo-600/30'
                        : 'hover:bg-surface-2 text-primary border border-transparent'"
                    v-on:click="navigateTo(null)"
                >
                    <Home class="w-4 h-4 shrink-0" :stroke-width="2" />
                    <span class="flex-1 text-sm font-medium">{{ t("admin.media.rootFolder") }}</span>
                </button>
                <div
                    v-for="folder in flatFolders"
                    :key="folder.id"
                    class="group flex items-center gap-1"
                    :style="{ paddingLeft: `${folder.depth * 1}rem` }"
                >
                    <button
                        type="button"
                        class="flex-1 text-left px-3 py-2 rounded-lg transition-colors flex items-center gap-2"
                        :class="currentFolderId === folder.id
                            ? 'bg-indigo-600/15 text-indigo-400 border border-indigo-600/30'
                            : 'hover:bg-surface-2 text-primary border border-transparent'"
                        v-on:click="navigateTo(folder.id)"
                    >
                        <Folder class="w-4 h-4 shrink-0" :stroke-width="2" />
                        <span class="flex-1 text-sm truncate">{{ folder.name }}</span>
                    </button>
                    <div class="opacity-0 group-hover:opacity-100 flex gap-0.5 transition-opacity">
                        <AppIconButton color="indigo" v-on:click="openEditFolder(folder)">
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
        <main class="flex-1 min-w-0 space-y-4">
            <!-- Breadcrumbs + toolbar -->
            <div class="flex items-center gap-2 flex-wrap">
                <nav class="flex items-center gap-1 text-sm text-muted">
                    <button type="button" class="hover:text-primary" v-on:click="navigateTo(null)">{{ t("admin.media.rootFolder") }}</button>
                    <template v-for="crumb in breadcrumbs" :key="crumb.id">
                        <ChevronRight class="w-3 h-3" :stroke-width="2" />
                        <button type="button" class="hover:text-primary" v-on:click="navigateTo(crumb.id)">{{ crumb.name }}</button>
                    </template>
                </nav>

                <div class="ml-auto flex items-center gap-2 flex-wrap">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" :stroke-width="2" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="t('admin.media.searchPlaceholder')"
                            class="pl-9 pr-4 py-2 rounded-lg bg-surface-2 border border-line/60 text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                            v-on:keyup.enter="runSearch"
                        >
                    </div>
                    <input ref="uploadInput" type="file" accept="image/*" multiple class="hidden" v-on:change="uploadFiles">
                    <AppButton variant="primary" size="md" :loading="uploading" v-on:click="uploadInput?.click()">
                        <Upload class="w-4 h-4" :stroke-width="2" />
                        {{ t("admin.media.upload") }}
                    </AppButton>
                </div>
            </div>

            <AppMessage v-if="media.some((m) => !m.alt)" variant="warning">
                {{ t("admin.media.altWarning") }}
            </AppMessage>

            <AppNoData v-if="!media.length" :message="t('admin.media.empty')" />
            <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                <div
                    v-for="item in media"
                    :key="item.id"
                    class="group relative bg-surface border border-line/60 rounded-lg overflow-hidden cursor-pointer hover:border-indigo-400 transition-colors"
                    v-on:click="openEditMedia(item)"
                >
                    <div class="relative aspect-square bg-surface-2 flex items-center justify-center overflow-hidden">
                        <img v-if="item.isImage" :src="item.url" :alt="item.alt ?? ''" class="w-full h-full object-cover">
                        <ImageIcon v-else class="w-10 h-10 text-muted" :stroke-width="1.5" />
                        <div v-if="!item.alt" class="absolute top-1 right-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-rose-500/80 text-white">
                            {{ t("admin.media.missingAlt") }}
                        </div>
                    </div>
                    <div class="p-2 space-y-0.5">
                        <div class="text-xs font-medium text-primary truncate">{{ item.originalName }}</div>
                        <div class="text-[10px] text-muted">{{ formatSize(item.size) }}<span v-if="item.width"> · {{ item.width }}×{{ item.height }}</span></div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Edit media modal -->
        <AppModal :show="!!editingMedia" max-width="3xl" v-on:close="closeEditMedia" scrollable>
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
                            class="absolute w-6 h-6 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-indigo-500 shadow-lg pointer-events-none"
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
                            class="text-indigo-400 hover:underline"
                            v-on:click="resetFocalPoint"
                        >
                            {{ t("admin.media.resetFocal") }}
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <AppInput
                        v-model="editForm.alt"
                        :label="t('admin.media.alt') + ' *'"
                        :error="editErrors.alt ?? ''"
                        :placeholder="t('admin.media.altPlaceholder')"
                    />
                    <AppTextarea
                        v-model="editForm.caption"
                        :label="t('admin.media.caption')"
                        :rows="3"
                    />
                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t("admin.media.folder") }}</label>
                        <AppSelect v-model="editForm.folderId">
                            <option :value="null">{{ t("admin.media.rootFolder") }}</option>
                            <option v-for="folder in flatFolders" :key="folder.id" :value="folder.id">
                                {{ "— ".repeat(folder.depth) }}{{ folder.name }}
                            </option>
                        </AppSelect>
                    </div>

                    <dl class="text-xs text-muted space-y-0.5 pt-2 border-t border-line">
                        <div class="flex justify-between"><dt>{{ t("admin.media.filename") }}</dt><dd class="font-mono">{{ editingMedia?.originalName }}</dd></div>
                        <div class="flex justify-between"><dt>{{ t("admin.media.size") }}</dt><dd>{{ formatSize(editingMedia?.size ?? 0) }}</dd></div>
                        <div v-if="editingMedia?.width" class="flex justify-between"><dt>{{ t("admin.media.dimensions") }}</dt><dd>{{ editingMedia.width }}×{{ editingMedia.height }}</dd></div>
                        <div class="flex justify-between"><dt>{{ t("admin.media.mimeType") }}</dt><dd>{{ editingMedia?.mimeType }}</dd></div>
                    </dl>
                </div>

                <div class="md:col-span-2 flex items-center justify-between pt-2 border-t border-line">
                    <AppButton variant="danger" size="md" v-on:click="deletingMedia = editingMedia; editingMedia = null">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("common.delete") }}
                    </AppButton>
                    <div class="flex items-center gap-2">
                        <AppButton variant="ghost" size="md" v-on:click="closeEditMedia">{{ t("common.cancel") }}</AppButton>
                        <AppButton type="submit" variant="primary" size="md" :loading="editSaving">{{ t("common.save") }}</AppButton>
                    </div>
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
                <div>
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t("admin.media.parentFolder") }}</label>
                    <AppSelect v-model="folderForm.parentId">
                        <option :value="null">{{ t("admin.media.rootFolder") }}</option>
                        <option v-for="folder in folderParentOptions" :key="folder.id" :value="folder.id">
                            {{ "— ".repeat(folder.depth) }}{{ folder.name }}
                        </option>
                    </AppSelect>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="folderModal.open = false">{{ t("common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="folderModal.saving">{{ t("common.save") }}</AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal :show="!!deletingMedia" max-width="sm" v-on:close="deletingMedia = null">
            <p class="text-sm text-primary">{{ t("admin.media.deleteConfirm", { name: deletingMedia?.originalName }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="deletingMedia = null">{{ t("common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDeleteMedia">{{ t("common.delete") }}</AppButton>
            </div>
        </AppModal>

        <AppModal :show="!!deletingFolder" max-width="sm" v-on:close="deletingFolder = null">
            <p class="text-sm text-primary">{{ t("admin.media.deleteFolderConfirm", { name: deletingFolder?.name }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="deletingFolder = null">{{ t("common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDeleteFolder">{{ t("common.delete") }}</AppButton>
            </div>
        </AppModal>
    </div>
</template>
