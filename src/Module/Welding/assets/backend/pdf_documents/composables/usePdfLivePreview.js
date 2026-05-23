import { ref } from "vue";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { PdfFieldType } from "../../pdf_constants/pdfFieldType.js";

export function usePdfLivePreview() {
    const fieldPositions = ref({});
    const loading = ref(false);

    let cachedTemplateId = null;
    let cachedOriginalBytes = null;

    async function loadPdfLib() {
        const { PDFDocument } = await import("pdf-lib");
        return { PDFDocument };
    }

    async function fetchPdf(fileUrl) {
        const res = await fetch(fileUrl);
        if (!res.ok) throw new Error("Failed to fetch PDF");
        return res.arrayBuffer();
    }

    function findPageIndex(pages, widgetRef, pdfDoc) {
        for (let i = 0; i < pages.length; i++) {
            try {
                const annots = pages[i].node.get(pdfDoc.context.obj("Annots"));
                if (annots?.asArray) {
                    const found = annots
                        .asArray()
                        .some(
                            (ref) => ref.toString() === widgetRef?.toString(),
                        );
                    if (found) return i;
                }
            } catch {}
        }
        return 0;
    }

    function extractFieldPositions(pdfDoc, fields) {
        const positions = {};
        const pages = pdfDoc.getPages();
        const form = pdfDoc.getForm();

        for (const field of fields) {
            try {
                const isRadio = field.fieldType === "radio";
                let pdfField;
                try {
                    pdfField = form.getField(field.pdfFieldName);
                } catch {
                    continue;
                }

                const typeName = pdfField.constructor.name;
                const isRadioGroup =
                    typeName === PdfFieldType.RadioGroup || isRadio;

                if (isRadioGroup) {
                    // Récupérer les options et tous les widgets du groupe
                    let options = [];
                    try {
                        options = pdfField.getOptions();
                    } catch {}

                    // Collecter tous les widgets du groupe via les pages (plus fiable que getWidgets())
                    const radioWidgets = [];
                    for (let pi = 0; pi < pages.length; pi++) {
                        try {
                            const annots = pages[pi].node.get(
                                pdfDoc.context.obj("Annots"),
                            );
                            if (!annots?.asArray) continue;
                            for (const annotRef of annots.asArray()) {
                                try {
                                    const annot =
                                        pdfDoc.context.lookup(annotRef);
                                    // Vérifier que c'est un widget de ce champ (via parent ou T)
                                    const parentRef = annot.get(
                                        pdfDoc.context.obj("Parent"),
                                    );
                                    if (!parentRef) continue;
                                    const parent =
                                        pdfDoc.context.lookup(parentRef);
                                    const tVal = parent?.get?.(
                                        pdfDoc.context.obj("T"),
                                    );
                                    const fieldName =
                                        tVal?.decodeText?.() ??
                                        tVal?.value ??
                                        "";
                                    if (fieldName !== field.pdfFieldName)
                                        continue;

                                    const rectArr = annot.get(
                                        pdfDoc.context.obj("Rect"),
                                    );
                                    if (!rectArr?.asArray) continue;
                                    const [x1, y1, x2, y2] = rectArr
                                        .asArray()
                                        .map(
                                            (n) =>
                                                n.numberValue ?? n.value ?? 0,
                                        );
                                    radioWidgets.push({
                                        pageIndex: pi,
                                        x: Math.min(x1, x2),
                                        y: Math.min(y1, y2),
                                        width: Math.abs(x2 - x1),
                                        height: Math.abs(y2 - y1),
                                    });
                                } catch {}
                            }
                        } catch {}
                    }

                    radioWidgets.forEach((w, idx) => {
                        positions[`__radio__${field.pdfFieldName}__${idx}`] = {
                            ...w,
                            fieldType: "radio",
                            radioGroupName: field.pdfFieldName,
                            optionValue: options[idx] ?? String(idx + 1),
                        };
                    });

                    // Fallback si l'itération des pages n'a rien trouvé
                    if (!radioWidgets.length) {
                        const widgets = pdfField.acroField.getWidgets();
                        widgets.forEach((widget, idx) => {
                            const rect = widget.getRectangle();
                            const pageIndex = findPageIndex(
                                pages,
                                widget.ref,
                                pdfDoc,
                            );
                            positions[
                                `__radio__${field.pdfFieldName}__${idx}`
                            ] = {
                                pageIndex,
                                x: rect.x,
                                y: rect.y,
                                width: rect.width,
                                height: rect.height,
                                fieldType: "radio",
                                radioGroupName: field.pdfFieldName,
                                optionValue: options[idx] ?? String(idx + 1),
                            };
                        });
                    }
                } else {
                    const widgets = pdfField.acroField.getWidgets();
                    if (!widgets.length) continue;
                    const widget = widgets[0];
                    const rect = widget.getRectangle();
                    const pageIndex = findPageIndex(pages, widget.ref, pdfDoc);
                    positions[field.pdfFieldName] = {
                        pageIndex,
                        x: rect.x,
                        y: rect.y,
                        width: rect.width,
                        height: rect.height,
                        fieldType: field.fieldType ?? "text",
                    };
                }
            } catch {}
        }
        return positions;
    }

    async function render(template, fieldValues, fields) {
        if (!template?.fileUrl) return;
        loading.value = true;
        try {
            const { PDFDocument } = await loadPdfLib();

            if (cachedTemplateId !== template.id) {
                cachedOriginalBytes = await fetchPdf(template.fileUrl);
                cachedTemplateId = template.id;
            }

            const pdfDoc = await PDFDocument.load(cachedOriginalBytes, {
                ignoreEncryption: true,
            });

            if (!Object.keys(fieldPositions.value).length && fields?.length) {
                fieldPositions.value = extractFieldPositions(pdfDoc, fields);
            }
        } catch (e) {
            console.warn("[PdfLivePreview]", e);
        } finally {
            loading.value = false;
        }
    }

    const debouncedRender = useDebounce(render, 350);

    function reset() {
        cachedTemplateId = null;
        cachedOriginalBytes = null;
        fieldPositions.value = {};
    }

    return { fieldPositions, loading, render, debouncedRender, reset };
}
