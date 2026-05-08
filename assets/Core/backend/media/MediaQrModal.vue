<script setup>
import { ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { Download } from "lucide-vue-next";
import QRCode from "qrcode";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppButton from "@/shared/components/action/AppButton.vue";

const { t } = useI18n();

const props = defineProps({
    media: { type: Object, default: null },
});

const emit = defineEmits(["close"]);

const qrDataUrl = ref("");

function permalink(item) {
    return item.permalink ?? window.location.origin + item.url;
}

watch(
    () => props.media,
    async (item) => {
        if (!item) {
            qrDataUrl.value = "";
            return;
        }
        qrDataUrl.value = await QRCode.toDataURL(permalink(item), {
            width: 256,
            margin: 2,
        });
    },
    { immediate: true },
);
</script>

<template>
    <AppModal :show="!!media" max-width="sm" v-on:close="emit('close')">
        <h3 class="text-sm font-medium text-primary mb-4">{{ t("backend.media.qrCode") }} — {{ media?.originalName }}</h3>
        <div class="flex flex-col items-center gap-4">
            <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR Code" class="w-48 h-48 rounded-xl border border-line/60">
            <p class="text-xs text-muted text-center break-all">{{ media ? permalink(media) : '' }}</p>
            <a v-if="qrDataUrl" :href="qrDataUrl" download="qrcode.png">
                <AppButton size="sm" variant="ghost"><Download class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.media.downloadQr") }}</AppButton>
            </a>
        </div>
    </AppModal>
</template>
