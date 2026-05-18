<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { FileText } from "lucide-vue-next";

const props = defineProps({
    url: { type: String, required: true },
});

const canvas = ref(null);
const rendered = ref(false);
const failed = ref(false);

let renderTask = null;
let pdfjsLib = null;

async function loadPdfJs() {
    if (pdfjsLib) return pdfjsLib;
    pdfjsLib = await import("pdfjs-dist");
    if (!pdfjsLib.GlobalWorkerOptions.workerSrc) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = new URL(
            "pdfjs-dist/build/pdf.worker.min.mjs",
            import.meta.url,
        ).href;
    }
    return pdfjsLib;
}

onMounted(async () => {
    const el = canvas.value;
    if (!el) return;

    try {
        const lib = await loadPdfJs();
        const pdf = await lib.getDocument({ url: props.url }).promise;
        const page = await pdf.getPage(1);

        const naturalViewport = page.getViewport({ scale: 1 });
        const containerWidth = el.parentElement?.offsetWidth ?? 256;
        const scale = containerWidth / naturalViewport.width;
        const viewport = page.getViewport({ scale });

        el.width = viewport.width;
        el.height = viewport.height;

        renderTask = page.render({ canvasContext: el.getContext("2d"), viewport });
        await renderTask.promise;
        rendered.value = true;
    } catch {
        failed.value = true;
    }
});

onUnmounted(() => renderTask?.cancel());
</script>

<template>
    <div class="absolute inset-0 overflow-hidden">
        <canvas
            ref="canvas"
            class="w-full transition-opacity duration-300"
            :class="rendered ? 'opacity-100' : 'opacity-0'"
        />
        <div v-if="!rendered" class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 bg-red-500/5">
            <FileText class="w-10 h-10 text-red-400/70" :stroke-width="1.5" />
            <span v-if="!failed" class="text-xs font-semibold text-red-400/60 uppercase tracking-widest animate-pulse">PDF</span>
            <span v-else class="text-xs font-semibold text-red-400/60 uppercase tracking-widest">PDF</span>
        </div>
    </div>
</template>
