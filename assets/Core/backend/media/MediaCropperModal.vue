<script setup>
import { nextTick, ref, watch } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { Save, X } from "lucide-vue-next";
import Cropper from "cropperjs";
import "cropperjs/dist/cropper.css";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppButton from "@/shared/components/action/AppButton.vue";

const { t } = useI18n();
const { loading: saving, request } = useRequest();

const props = defineProps({
    media: { type: Object, default: null },
    cropPath: { type: String, required: true },
});

const emit = defineEmits(["close", "cropped"]);

const SNAP_THRESHOLD = 12;
const imageEl = ref(null);
let cropper = null;

watch(
    () => props.media,
    async (item) => {
        if (!item) {
            cropper?.destroy();
            cropper = null;
            return;
        }
        await nextTick();
        if (!imageEl.value) return;
        cropper?.destroy();
        cropper = new Cropper(imageEl.value, {
            viewMode: 1,
            autoCropArea: 0.9,
            movable: true,
            zoomable: true,
            background: true,
            cropmove: snapToEdges,
        });
    },
);

function snapToEdges() {
    if (!cropper) return;
    const d = cropper.getData(true);
    const img = cropper.getImageData();
    let { x, y, width, height } = d;
    let changed = false;
    if (x < SNAP_THRESHOLD) { x = 0; changed = true; }
    if (y < SNAP_THRESHOLD) { y = 0; changed = true; }
    if (x + width > img.naturalWidth - SNAP_THRESHOLD) { width = img.naturalWidth - x; changed = true; }
    if (y + height > img.naturalHeight - SNAP_THRESHOLD) { height = img.naturalHeight - y; changed = true; }
    if (changed) cropper.setData({ x, y, width, height });
}

function close() {
    cropper?.destroy();
    cropper = null;
    emit("close");
}

async function save() {
    if (!cropper || !props.media) return;
    const d = cropper.getData(true);
    const url = buildPath(props.cropPath, { id: props.media.id });
    const data = await request(url, { x: d.x, y: d.y, width: d.width, height: d.height });
    if (!data || !data.success) return;
    emit("cropped", data.media);
    toast.success(t("backend.media.cropped"));
    close();
}
</script>

<template>
    <AppModal :show="!!media" max-width="5xl" :closeable="false" v-on:close="close">
        <h3 class="text-sm font-semibold text-primary mb-3">{{ t("backend.media.cropTitle") }} — {{ media?.originalName }}</h3>
        <div style="height: 65vh; width: 100%; overflow: hidden;">
            <img
                v-if="media"
                ref="imageEl"
                :src="media.url"
                :alt="media.alt ?? ''"
                style="display: block; max-width: 100%;"
            >
        </div>
        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="close"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="primary" size="md" :loading="saving" v-on:click="save">
                    <Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.media.applyCrop") }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>
</template>
