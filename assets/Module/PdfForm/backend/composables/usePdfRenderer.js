import { ref, watch, nextTick, onMounted, onUnmounted } from "vue";

const ANNOTATION_MODE_NON_INTERACTIVE = 1;
const PDF_A4_WIDTH_PT = 595;
const PDF_MAX_SCALE = 1.8;
const PDF_MIN_FONT_SIZE = 9;
const PDF_FONT_SIZE_RATIO = 0.58;

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

function computeOverlay(viewport, pos, fieldName) {
    const [sx, , , sy, tx, ty] = viewport.transform;
    const x1 = sx * pos.x + tx;
    const y1 = sy * pos.y + ty;
    const x2 = sx * (pos.x + pos.width) + tx;
    const y2 = sy * (pos.y + pos.height) + ty;
    return {
        fieldName,
        fieldType: pos.fieldType,
        radioGroupName: pos.radioGroupName,
        optionValue: pos.optionValue,
        left: Math.min(x1, x2),
        top: Math.min(y1, y2),
        width: Math.abs(x2 - x1),
        height: Math.abs(y2 - y1),
        fontSize: Math.max(Math.abs(y2 - y1) * PDF_FONT_SIZE_RATIO, PDF_MIN_FONT_SIZE),
    };
}

function buildPageOverlays(viewport, pageIndex, fieldPositions) {
    return Object.entries(fieldPositions)
        .filter(([, pos]) => pos.pageIndex === pageIndex)
        .map(([fieldName, pos]) => computeOverlay(viewport, pos, fieldName));
}

export function usePdfRenderer(containerRef, fieldPositions) {
    const pages = ref([]);
    const loading = ref(false);
    const canvasEls = {};
    let pdfDocInstance = null;
    let currentUrl = null;

    function setCanvasRef(el, i) {
        if (el) canvasEls[i] = el;
        else delete canvasEls[i];
    }

    function getScale() {
        return Math.min((containerRef.value?.clientWidth ?? 700) / PDF_A4_WIDTH_PT, PDF_MAX_SCALE);
    }

    async function drawPages(pageList) {
        for (let i = 0; i < pageList.length; i++) {
            const canvas = canvasEls[i];
            if (!canvas) continue;
            const { page, viewport } = pageList[i];
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            await page.render({
                canvasContext: canvas.getContext("2d"),
                viewport,
                annotationMode: ANNOTATION_MODE_NON_INTERACTIVE,
            }).promise;
        }
    }

    async function load(url) {
        if (!url) return;
        currentUrl = url;
        loading.value = true;

        try {
            const pdfjs = await loadPdfJs();
            if (pdfDocInstance) { await pdfDocInstance.destroy(); pdfDocInstance = null; }

            pdfDocInstance = await pdfjs.getDocument(url).promise;
            const scale = getScale();
            const newPages = [];

            for (let num = 1; num <= pdfDocInstance.numPages; num++) {
                const page = await pdfDocInstance.getPage(num);
                const viewport = page.getViewport({ scale });
                newPages.push({
                    pageNum: num,
                    width: Math.floor(viewport.width),
                    height: Math.floor(viewport.height),
                    viewport,
                    page,
                    overlays: buildPageOverlays(viewport, num - 1, fieldPositions.value),
                });
            }

            pages.value = newPages;
            await nextTick();
            await drawPages(newPages);
        } catch (exception) {
            console.warn("[usePdfRenderer]", exception);
        } finally {
            loading.value = false;
        }
    }

    // Recalcule les overlays quand les positions de champs arrivent après le rendu initial
    watch(fieldPositions, (positions) => {
        if (!pages.value.length || !Object.keys(positions).length) return;
        pages.value = pages.value.map((p) => ({
            ...p,
            overlays: buildPageOverlays(p.viewport, p.pageNum - 1, positions),
        }));
    }, { deep: true });

    onMounted(() => {
        if (fieldPositions.value !== undefined) {
            // pdfUrl sera passé explicitement via load()
        }
    });

    onUnmounted(async () => {
        if (pdfDocInstance) await pdfDocInstance.destroy();
    });

    return { pages, loading, setCanvasRef, load, currentUrl: { get: () => currentUrl } };
}
