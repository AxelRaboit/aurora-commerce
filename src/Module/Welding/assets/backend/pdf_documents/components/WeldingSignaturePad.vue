<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";
import { Eraser } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";

const { t } = useI18n();

const emit = defineEmits(["update:modelValue"]);

const canvas = ref(null);
const isEmpty = ref(true);

let drawing = false;
let ctx = null;
let lastX = 0;
let lastY = 0;

function getPos(e) {
    const rect = canvas.value.getBoundingClientRect();
    const source = e.touches?.[0] ?? e;
    return [source.clientX - rect.left, source.clientY - rect.top];
}

function startDraw(e) {
    e.preventDefault();
    drawing = true;
    [lastX, lastY] = getPos(e);
}

function draw(e) {
    e.preventDefault();
    if (!drawing) return;
    const [x, y] = getPos(e);
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(x, y);
    ctx.stroke();
    [lastX, lastY] = [x, y];
    isEmpty.value = false;
    emit("update:modelValue", canvas.value.toDataURL("image/png"));
}

function stopDraw() {
    drawing = false;
}

function clear() {
    ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
    isEmpty.value = true;
    emit("update:modelValue", null);
}

function resizeCanvas() {
    const el = canvas.value;
    const ratio = window.devicePixelRatio ?? 1;
    const width = el.offsetWidth;
    const height = el.offsetHeight;
    el.width = width * ratio;
    el.height = height * ratio;
    ctx.scale(ratio, ratio);
    setupCtx();
}

function setupCtx() {
    ctx.strokeStyle = "#1a1a1a";
    ctx.lineWidth = 2;
    ctx.lineCap = "round";
    ctx.lineJoin = "round";
}

onMounted(() => {
    ctx = canvas.value.getContext("2d");
    resizeCanvas();
    window.addEventListener("resize", resizeCanvas);
});

onUnmounted(() => {
    window.removeEventListener("resize", resizeCanvas);
});
</script>

<template>
    <div class="space-y-2">
        <div class="relative rounded-lg border-2 border-dashed border-line bg-white overflow-hidden" style="height: 200px">
            <canvas
                ref="canvas"
                class="absolute inset-0 w-full h-full touch-none cursor-crosshair"
                v-on:mousedown="startDraw"
                v-on:mousemove="draw"
                v-on:mouseup="stopDraw"
                v-on:mouseleave="stopDraw"
                v-on:touchstart="startDraw"
                v-on:touchmove="draw"
                v-on:touchend="stopDraw"
            />
            <p v-if="isEmpty" class="absolute inset-0 flex items-center justify-center text-sm text-muted pointer-events-none select-none">
                {{ t("backend.welding.pdf_documents.signatureHint") }}
            </p>
        </div>
        <AppButton
            type="button"
            variant="ghost"
            size="sm"
            :disabled="isEmpty"
            v-on:click="clear"
        >
            <Eraser class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.welding.pdf_documents.signatureClear") }}
        </AppButton>
    </div>
</template>
