<script setup>
import { useI18n } from "vue-i18n";
import { Image as ImageIcon, X } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppImage from "@/shared/components/display/AppImage.vue";
import { openMediaPicker } from "@/shared/utils/mediaPicker.js";
import { openDocumentPicker } from "@/shared/utils/documentPicker.js";

const props = defineProps({
    label: { type: String, default: "" },
    hint: { type: String, default: "" },
    modelValue: { type: Object, default: () => ({ id: null, url: null }) },
    chooseLabel: { type: String, default: "" },
    changeLabel: { type: String, default: "" },
    removeLabel: { type: String, default: "" },
    size: { type: Number, default: 128 },
    /**
     * Where the picker pulls assets from. `media` (default) keeps Aurora's
     * historical Media library. `document` switches to the GED Document
     * library — set during the Phase 2 migration of consumers whose
     * underlying FK is now `document_id` instead of `media_id`. Once the
     * full migration completes the default flips and `media` disappears.
     */
    source: {
        type: String,
        default: "media",
        validator: (value) => ["media", "document"].includes(value),
    },
});

const emit = defineEmits(["update:modelValue"]);

const { t } = useI18n();

async function pick() {
    const item = "document" === props.source
        ? await openDocumentPicker({ imagesOnly: true })
        : await openMediaPicker({ imagesOnly: true });
    if (!item) return;
    // Documents serialize their public URL under `fileUrl`; Media uses `url`.
    // The component normalizes them so consumers stay agnostic.
    const url = item.url ?? item.fileUrl ?? null;
    emit("update:modelValue", { id: item.id, url });
}

function clear() {
    emit("update:modelValue", { id: null, url: null });
}
</script>

<template>
    <div>
        <p v-if="label" class="text-xs font-medium text-secondary uppercase tracking-wide mb-1.5">{{ label }}</p>
        <div v-if="modelValue?.url" class="flex items-start gap-3">
            <button
                type="button"
                class="group relative rounded-lg border border-line overflow-hidden shrink-0"
                :style="{ width: `${size}px`, height: `${size}px` }"
                v-on:click="pick"
            >
                <AppImage :src="modelValue.url" alt="" object-fit="cover" />
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all">
                    <span class="text-white text-xs font-medium">{{ changeLabel || t('shared.media.change') }}</span>
                </div>
            </button>
            <div class="flex flex-col gap-2">
                <AppButton variant="secondary" size="sm" type="button" v-on:click="pick">
                    <ImageIcon class="w-4 h-4" :stroke-width="2" />
                    {{ changeLabel || t('shared.media.change') }}
                </AppButton>
                <AppButton variant="ghost" size="sm" type="button" v-on:click="clear">
                    <X class="w-4 h-4" :stroke-width="2" />
                    {{ removeLabel || t('shared.media.remove') }}
                </AppButton>
            </div>
        </div>
        <AppButton
            v-else
            variant="secondary"
            size="sm"
            type="button"
            v-on:click="pick"
        >
            <ImageIcon class="w-4 h-4" :stroke-width="2" />
            {{ chooseLabel || t('shared.media.choose') }}
        </AppButton>
        <p v-if="hint" class="text-xs text-muted mt-2">{{ hint }}</p>
    </div>
</template>
