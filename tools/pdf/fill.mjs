/**
 * Aurora PDF filler — fills AcroForm fields with Unicode support, force-regenerates
 * widget appearances (so PDF.js and pdf-lib's flatten both render correctly),
 * embeds a canvas-captured signature (PNG data URL under the special key
 * `__signature__`) onto the first AcroForm signature field, and optionally flattens.
 *
 * Usage: node fill.mjs <inputPdf> <outputPdf> <valuesJson> [--flatten]
 *
 * Signature protocol — the frontend's `SignaturePad.vue` emits a PNG via
 * `canvas.toDataURL("image/png")`. `usePdfDocumentsForm.js` injects this into
 * the values payload under the reserved key `__signature__`. We extract it
 * here, draw it (aspect-preserved, centered) over each signature widget's
 * rectangle, then remove the signature field from the form so `flatten()`
 * does not choke on widgets without a valid /AP/N appearance stream.
 */

import { readFileSync, writeFileSync } from "node:fs";
import { PDFDocument, PDFName, PDFRef } from "pdf-lib";

const PdfFieldType = Object.freeze({
    TextField: "PDFTextField",
    CheckBox: "PDFCheckBox",
    RadioGroup: "PDFRadioGroup",
    Dropdown: "PDFDropdown",
    OptionList: "PDFOptionList",
    Button: "PDFButton",
    Signature: "PDFSignature",
});

const SIGNATURE_VALUE_KEY = "__signature__";

const UNICODE_FONT_PATH = new URL("./fonts/ubuntu-regular.ttf", import.meta.url).pathname;

function parseArgs(argv) {
    const args = argv.slice(2);
    const flatten = args.includes("--flatten");
    const [inputPath, outputPath, valuesPath] = args.filter((a) => !a.startsWith("--"));
    if (!inputPath || !outputPath || !valuesPath) {
        process.stderr.write("Usage: node fill.mjs <input.pdf> <output.pdf> <values.json> [--flatten]\n");
        process.exit(1);
    }
    return { inputPath, outputPath, valuesPath, flatten };
}

async function tryEmbedUnicodeFont(pdfDoc) {
    try {
        return await pdfDoc.embedFont(readFileSync(UNICODE_FONT_PATH), { subset: true });
    } catch {
        return null;
    }
}

function setFieldValue(field, value) {
    const typeName = field.constructor.name;
    if (typeName === PdfFieldType.CheckBox) {
        const checked = value && value !== "Off" && value !== "No" && value !== "";
        checked ? field.check() : field.uncheck();
        return;
    }
    if (typeName === PdfFieldType.RadioGroup || typeName === PdfFieldType.Dropdown) {
        if (value) field.select(String(value));
        return;
    }
    if (typeName === PdfFieldType.Signature) {
        // Handled separately by embedSignature — never call setText on a signature field.
        return;
    }
    field.setText(String(value ?? ""));
}

/**
 * Force-regenerate each field's appearance. Bypasses pdf-lib's needsAppearancesUpdate()
 * gate (which returns false for radios whose original /AP streams exist but render
 * badly in pdf-lib/PDF.js). Text-like fields get the Unicode font; buttons ignore it.
 */
function refreshAppearances(form, unicodeFont) {
    for (const field of form.getFields()) {
        const typeName = field.constructor.name;
        try {
            if (typeName === PdfFieldType.TextField || typeName === PdfFieldType.Dropdown) {
                unicodeFont ? field.updateAppearances(unicodeFont) : field.defaultUpdateAppearances();
            } else if (
                typeName === PdfFieldType.RadioGroup
                || typeName === PdfFieldType.CheckBox
                || typeName === PdfFieldType.Button
                || typeName === PdfFieldType.OptionList
            ) {
                field.defaultUpdateAppearances();
            }
        } catch {
            // some PDFs resist appearance updates — skip
        }
    }
}

function decodePngDataUrl(dataUrl) {
    if (typeof dataUrl !== "string") return null;
    const match = /^data:image\/png;base64,(.+)$/.exec(dataUrl);
    if (!match) return null;
    try {
        return Buffer.from(match[1], "base64");
    } catch {
        return null;
    }
}

function findPageForWidget(pdfDoc, widget, pages) {
    try {
        const pageRef = widget.dict.get(PDFName.of("P"));
        if (pageRef instanceof PDFRef) {
            return pages.find((p) => p.ref === pageRef) ?? null;
        }
    } catch {
        // dict access can throw on malformed widgets — fall through
    }
    return null;
}

/** Fit imgWidth×imgHeight inside rect while preserving aspect ratio, centered. */
function fitContain(imgWidth, imgHeight, rect) {
    if (imgWidth <= 0 || imgHeight <= 0) return { ...rect };
    const scale = Math.min(rect.width / imgWidth, rect.height / imgHeight);
    const drawWidth = imgWidth * scale;
    const drawHeight = imgHeight * scale;
    return {
        x: rect.x + (rect.width - drawWidth) / 2,
        y: rect.y + (rect.height - drawHeight) / 2,
        width: drawWidth,
        height: drawHeight,
    };
}

/**
 * Embed the canvas signature (PNG data URL) over the first AcroForm signature
 * field's widget rectangle(s), then remove the field so flatten() succeeds.
 * No-op if there is no signature data, no signature field, or no valid widget.
 */
async function embedSignature(pdfDoc, form, dataUrl) {
    if (!dataUrl) return;

    const sigField = form.getFields().find(
        (f) => f.constructor.name === PdfFieldType.Signature,
    );
    if (!sigField) return;

    const widgets = sigField.acroField.getWidgets();
    if (widgets.length === 0) return;

    const pngBytes = decodePngDataUrl(dataUrl);
    if (!pngBytes) return;

    let pngImage;
    try {
        pngImage = await pdfDoc.embedPng(pngBytes);
    } catch {
        return;
    }

    const pages = pdfDoc.getPages();
    for (const widget of widgets) {
        let rect;
        try {
            rect = widget.getRectangle();
        } catch {
            continue;
        }
        if (!rect || rect.width <= 0 || rect.height <= 0) continue;

        const page = findPageForWidget(pdfDoc, widget, pages) ?? pages[0];
        if (!page) continue;

        const box = fitContain(pngImage.width, pngImage.height, rect);
        page.drawImage(pngImage, box);
    }

    // Manual cleanup — pdf-lib's form.removeField() doesn't always detach the
    // widget annot reliably (depends on merged vs split widget+field structure),
    // and leaving the signature widget in place crashes form.flatten() because
    // a Sig widget has no /AP/N stream. Strip the widget from page annots and
    // the field from AcroForm /Fields directly.
    detachSignatureField(pdfDoc, form, sigField);
}

function detachSignatureField(pdfDoc, form, sigField) {
    // Single ref for "merged" widget+field PDFs (Acrobat default); for "split"
    // PDFs (separate field dict with /Kids), the widget refs differ — handle
    // both by collecting all candidates.
    const refsToRemove = new Set();
    refsToRemove.add(sigField.ref);
    try {
        for (const widget of sigField.acroField.getWidgets()) {
            const r = widget.ref ?? widget.dict?.context?.getObjectRef?.(widget.dict);
            if (r) refsToRemove.add(r);
        }
    } catch {
        // best-effort
    }

    // 1. Strip widget annotations from each page
    for (const page of pdfDoc.getPages()) {
        const annots = page.node.Annots();
        if (!annots?.asArray) continue;
        const arr = annots.asArray();
        for (let i = arr.length - 1; i >= 0; i--) {
            if (refsToRemove.has(arr[i])) {
                annots.remove(i);
            }
        }
    }

    // 2. Strip the field from AcroForm /Fields
    try {
        const fields = form.acroForm.dict.lookup(PDFName.of("Fields"));
        if (fields?.asArray) {
            const arr = fields.asArray();
            for (let i = arr.length - 1; i >= 0; i--) {
                if (refsToRemove.has(arr[i])) {
                    fields.remove(i);
                }
            }
        }
    } catch {
        // best-effort — if /Fields cleanup fails, the worst case is that
        // flatten() still trips, which we wrap in try/catch in main()
    }
}

async function main() {
    const { inputPath, outputPath, valuesPath, flatten } = parseArgs(process.argv);

    const pdfDoc = await PDFDocument.load(readFileSync(inputPath), { ignoreEncryption: true });
    const form = pdfDoc.getForm();
    const unicodeFont = await tryEmbedUnicodeFont(pdfDoc);

    const fieldValues = JSON.parse(readFileSync(valuesPath, "utf-8"));
    const signatureDataUrl = fieldValues[SIGNATURE_VALUE_KEY];
    delete fieldValues[SIGNATURE_VALUE_KEY];

    for (const [fieldName, value] of Object.entries(fieldValues)) {
        try {
            setFieldValue(form.getField(fieldName), value);
        } catch {
            // field missing or type mismatch — skip silently
        }
    }

    await embedSignature(pdfDoc, form, signatureDataUrl);

    refreshAppearances(form, unicodeFont);

    // Pre-pass above sets needsAppearancesUpdate()=false everywhere, so flatten's
    // internal refresh preserves our work. Disabling it would crash on widgets
    // that never had a valid /AP/N stream. Belt-and-braces try/catch: if a
    // residual signature widget (or any other widget missing /AP) survived our
    // cleanup, the saved PDF still renders correctly — the drawn signature
    // image is already on the page.
    if (flatten) {
        try {
            form.flatten();
        } catch (e) {
            process.stderr.write(`[fill.mjs] flatten skipped: ${e.message}\n`);
        }
    }

    writeFileSync(outputPath, await pdfDoc.save());
}

await main();
