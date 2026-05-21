<script setup>
import { useI18n } from "vue-i18n";
import { Plus, Trash2, GripVertical, Palette, X, SearchX } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppTextarea from "@/shared/components/form/input/AppTextarea.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import {
    usePostItNotesPage,
    POST_IT_COLORS,
} from "./composables/usePostItNotesPage.js";
import { usePostItDragDrop } from "./composables/usePostItDragDrop.js";
import { usePostItResize } from "./composables/usePostItResize.js";

const props = defineProps({
    listPath: { type: String, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    movePath: { type: String, required: true },
    resizePath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

const { t } = useI18n();

const {
    notes,
    loading,
    isEmpty,
    palettePickerOpenFor,
    pendingDelete,
    deleting,
    searchQuery,
    filteredNotes,
    isFiltering,
    hasNoMatches,
    createNote,
    scheduleSave,
    persistMove,
    persistResize,
    requestDelete,
    cancelDelete,
    confirmDelete,
    setColor,
    togglePalette,
} = usePostItNotesPage(props);

const { startDrag } = usePostItDragDrop({ onMoveCommit: persistMove });
const { startResize } = usePostItResize({ onResizeCommit: persistResize });
</script>

<template>
    <div class="p-4 lg:p-6 space-y-4">
        <p class="text-sm text-muted">
            <template v-if="isFiltering">
                {{ filteredNotes.length }} / {{ notes.length }}
            </template>
            <template v-else>
                {{ notes.length }} {{ notes.length === 1 ? t("notes.post_it.count_one") : t("notes.post_it.count_other") }}
            </template>
        </p>

        <AppListToolbar>
            <AppSearchInput
                v-model="searchQuery"
                :placeholder="t('notes.post_it.search_placeholder')"
            />
            <template #actions>
                <AppButton
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    v-on:click="createNote"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t("notes.post_it.create") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <div
            class="post-it-board flex flex-col gap-3 md:block md:relative md:rounded-xl md:border md:border-line md:bg-surface-2/30 md:overflow-auto md:min-h-[70vh] md:gap-0"
        >
            <div
                v-if="loading"
                class="py-8 text-center md:absolute md:inset-0 md:py-0 md:flex md:items-center md:justify-center text-muted text-sm"
            >
                {{ t("notes.post_it.loading") }}
            </div>

            <div
                v-else-if="isEmpty"
                class="py-8 text-center md:absolute md:inset-0 md:py-0 md:flex md:flex-col md:items-center md:justify-center text-muted text-sm flex flex-col items-center gap-2"
            >
                <p>{{ t("notes.post_it.empty") }}</p>
                <AppButton variant="dashed" size="sm" v-on:click="createNote">
                    <Plus class="w-3.5 h-3.5" :stroke-width="2.5" />
                    {{ t("notes.post_it.create_first") }}
                </AppButton>
            </div>

            <div
                v-else-if="hasNoMatches"
                class="py-8 text-center md:absolute md:inset-0 md:py-0 md:flex md:flex-col md:items-center md:justify-center text-muted text-sm flex flex-col items-center gap-2"
            >
                <SearchX class="w-8 h-8 text-muted/50" :stroke-width="1.5" />
                <p>{{ t("notes.post_it.no_matches", { query: searchQuery }) }}</p>
            </div>

            <article
                v-for="note in filteredNotes"
                :key="note.id"
                class="post-it rounded-md shadow-md flex flex-col text-black/85 placeholder:text-black/35 md:absolute"
                :style="{
                    left: `${note.positionX}px`,
                    top: `${note.positionY}px`,
                    width: `${note.width}px`,
                    height: `${note.height}px`,
                    backgroundColor: note.color,
                }"
            >
                <header class="flex items-center justify-between px-1 py-0.5 border-b border-black/10 select-none">
                    <AppIconButton
                        color="on-light"
                        :title="t('notes.post_it.drag')"
                        class="hidden md:flex cursor-grab touch-none"
                        v-on:pointerdown="startDrag($event, note)"
                    >
                        <GripVertical class="w-3.5 h-3.5" :stroke-width="2" />
                    </AppIconButton>
                    <span class="md:hidden" />
                    <!-- empty spacer keeps the right-side group right-aligned via justify-between on mobile too -->


                    <div class="flex items-center gap-0.5">
                        <div class="relative">
                            <AppIconButton
                                color="on-light"
                                :title="t('notes.post_it.color')"
                                v-on:click="togglePalette(note)"
                            >
                                <Palette class="w-3.5 h-3.5" :stroke-width="2" />
                            </AppIconButton>
                            <div
                                v-if="palettePickerOpenFor === note.id"
                                class="absolute top-7 right-0 z-10 flex gap-1 p-1.5 rounded-md bg-white shadow-lg ring-1 ring-black/10"
                                v-on:click.stop
                            >
                                <button
                                    v-for="c in POST_IT_COLORS"
                                    :key="c"
                                    type="button"
                                    class="w-5 h-5 rounded-full ring-1 ring-black/10 hover:scale-110 transition-transform"
                                    :style="{ backgroundColor: c }"
                                    :aria-label="c"
                                    v-on:click="setColor(note, c)"
                                />
                            </div>
                        </div>

                        <AppIconButton
                            color="on-light"
                            :title="t('shared.common.delete')"
                            v-on:click="requestDelete(note)"
                        >
                            <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                </header>

                <AppInput
                    v-model="note.title"
                    variant="ghost"
                    :placeholder="t('notes.post_it.title_placeholder')"
                    class="px-2.5 py-1.5 text-sm font-semibold border-b border-black/5"
                    v-on:update:model-value="scheduleSave(note)"
                />

                <AppTextarea
                    v-model="note.content"
                    variant="ghost"
                    :placeholder="t('notes.post_it.content_placeholder')"
                    class="flex-1 min-h-0 px-2.5 py-2 text-sm"
                    v-on:update:model-value="scheduleSave(note)"
                />

                <div
                    class="post-it-resize-handle hidden md:block absolute bottom-0 right-0 w-4 h-4 cursor-nwse-resize touch-none"
                    :title="t('notes.post_it.resize')"
                    v-on:pointerdown="startResize($event, note)"
                />
            </article>
        </div>

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="!deleting"
            :title="t('notes.post_it.delete')"
            :icon="Trash2"
            v-on:close="cancelDelete"
        >
            <p class="text-sm text-primary">
                {{ t("notes.post_it.confirm_delete") }}
            </p>
            <p v-if="pendingDelete?.title" class="text-sm text-secondary mt-2">
                « {{ pendingDelete.title }} »
            </p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" :disabled="deleting" v-on:click="cancelDelete">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("notes.post_it.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="deleting" v-on:click="confirmDelete">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("notes.post_it.delete") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>

<style scoped>
.post-it {
    transition: box-shadow 150ms ease, transform 150ms ease;
}
.post-it:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}
/* Tiny corner-resize affordance — diagonal stripes hint at the grab zone. */
.post-it-resize-handle {
    background-image: linear-gradient(
        135deg,
        transparent 0%,
        transparent 50%,
        rgba(0, 0, 0, 0.2) 50%,
        rgba(0, 0, 0, 0.2) 60%,
        transparent 60%,
        transparent 70%,
        rgba(0, 0, 0, 0.2) 70%,
        rgba(0, 0, 0, 0.2) 80%,
        transparent 80%
    );
    border-bottom-right-radius: 0.375rem;
}

/* Mobile: kill the inline absolute positioning and fixed dimensions, stack
   the post-its as full-width cards. Override via !important because the
   inline `style` attribute carries higher CSS specificity than utilities.
   Above `md` the inline coordinates take effect and the free-form board is
   restored. */
@media (max-width: 767.98px) {
    .post-it {
        position: static !important;
        left: auto !important;
        top: auto !important;
        width: 100% !important;
        height: auto !important;
        min-height: 180px;
    }
}
</style>
