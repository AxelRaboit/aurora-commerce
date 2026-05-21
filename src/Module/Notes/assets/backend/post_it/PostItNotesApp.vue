<script setup>
import { useI18n } from "vue-i18n";
import { Plus, Trash2, GripVertical, Palette, X } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppTextarea from "@/shared/components/form/input/AppTextarea.vue";
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
        <div class="flex items-center justify-between">
            <p class="text-sm text-muted">
                {{ notes.length }} {{ notes.length === 1 ? t("notes.post_it.count_one") : t("notes.post_it.count_other") }}
            </p>
            <AppButton variant="primary" size="sm" v-on:click="createNote">
                <Plus class="w-4 h-4" :stroke-width="2.5" />
                {{ t("notes.post_it.create") }}
            </AppButton>
        </div>

        <div
            class="post-it-board relative rounded-xl border border-line bg-surface-2/30 overflow-auto"
            style="min-height: 70vh;"
        >
            <div v-if="loading" class="absolute inset-0 flex items-center justify-center text-muted text-sm">
                {{ t("notes.post_it.loading") }}
            </div>

            <div v-else-if="isEmpty" class="absolute inset-0 flex flex-col items-center justify-center text-muted text-sm gap-2">
                <p>{{ t("notes.post_it.empty") }}</p>
                <AppButton variant="dashed" size="sm" v-on:click="createNote">
                    <Plus class="w-3.5 h-3.5" :stroke-width="2.5" />
                    {{ t("notes.post_it.create_first") }}
                </AppButton>
            </div>

            <article
                v-for="note in notes"
                :key="note.id"
                class="post-it absolute rounded-md shadow-md flex flex-col text-black/85 placeholder:text-black/35"
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
                        class="cursor-grab touch-none"
                        v-on:pointerdown="startDrag($event, note)"
                    >
                        <GripVertical class="w-3.5 h-3.5" :stroke-width="2" />
                    </AppIconButton>

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
                    class="post-it-resize-handle absolute bottom-0 right-0 w-4 h-4 cursor-nwse-resize touch-none"
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
</style>
