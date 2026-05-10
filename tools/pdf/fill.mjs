/**
 * Aurora PDF filler — fills AcroForm fields with Unicode support, force-regenerates
 * widget appearances (so PDF.js and pdf-lib's flatten both render correctly), and
 * optionally flattens.
 *
 * Usage: node fill.mjs <inputPdf> <outputPdf> <valuesJson> [--flatten]
 */

import { readFileSync, writeFileSync } from "node:fs";
import { PDFDocument } from "pdf-lib";

const PdfFieldType = Object.freeze({
    TextField: "PDFTextField",
    CheckBox: "PDFCheckBox",
    RadioGroup: "PDFRadioGroup",
    Dropdown: "PDFDropdown",
    OptionList: "PDFOptionList",
    Button: "PDFButton",
});

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

async function main() {
    const { inputPath, outputPath, valuesPath, flatten } = parseArgs(process.argv);

    const pdfDoc = await PDFDocument.load(readFileSync(inputPath), { ignoreEncryption: true });
    const form = pdfDoc.getForm();
    const unicodeFont = await tryEmbedUnicodeFont(pdfDoc);

    const fieldValues = JSON.parse(readFileSync(valuesPath, "utf-8"));
    for (const [fieldName, value] of Object.entries(fieldValues)) {
        try {
            setFieldValue(form.getField(fieldName), value);
        } catch {
            // field missing or type mismatch — skip silently
        }
    }

    refreshAppearances(form, unicodeFont);

    // Pre-pass above sets needsAppearancesUpdate()=false everywhere, so flatten's
    // internal refresh preserves our work. Disabling it would crash on widgets
    // that never had a valid /AP/N stream.
    if (flatten) form.flatten();

    writeFileSync(outputPath, await pdfDoc.save());
}

await main();
