/**
 * Aurora PDF filler — fills AcroForm fields with Unicode support and flattens.
 * Usage: node pdf-fill.mjs <inputPdf> <outputPdf> <valuesJson>
 *
 * valuesJson: path to a JSON file mapping pdfFieldName → value string
 * Uses an embedded Unicode font so accented/special characters render correctly.
 */

import { readFileSync, writeFileSync } from "node:fs";
import { PDFDocument } from "pdf-lib";

const [, , inputPath, outputPath, valuesPath] = process.argv;

if (!inputPath || !outputPath || !valuesPath) {
    process.stderr.write("Usage: node pdf-fill.mjs <input.pdf> <output.pdf> <values.json>\n");
    process.exit(1);
}

const pdfBytes = readFileSync(inputPath);
const fieldValues = JSON.parse(readFileSync(valuesPath, "utf-8"));

const pdfDoc = await PDFDocument.load(pdfBytes, { ignoreEncryption: true });
const form = pdfDoc.getForm();

// Embed the bundled Unicode font so accented/special characters render correctly.
// tools/pdf/fonts/ubuntu-regular.ttf is shipped with the project — no system font dependency.
let unicodeFont = null;
try {
    const fontPath = new URL("./fonts/ubuntu-regular.ttf", import.meta.url).pathname;
    unicodeFont = await pdfDoc.embedFont(readFileSync(fontPath), { subset: true });
} catch {
    // Font file missing — flatten will use the PDF's own embedded font (may lack Unicode glyphs).
}

// Fill all fields
for (const [fieldName, value] of Object.entries(fieldValues)) {
    try {
        const field = form.getField(fieldName);
        const typeName = field.constructor.name;
        if (typeName === "PDFCheckBox") {
            const checked = value && value !== "Off" && value !== "No" && value !== "";
            checked ? field.check() : field.uncheck();
        } else if (typeName === "PDFRadioGroup") {
            if (value) field.select(String(value));
        } else if (typeName === "PDFDropdown") {
            if (value) field.select(String(value));
        } else {
            field.setText(String(value ?? ""));
        }
    } catch {
        // field not found or type mismatch — skip silently
    }
}

// Regenerate all field appearances with the Unicode font (if found)
if (unicodeFont) {
    try {
        form.updateFieldAppearances(unicodeFont);
    } catch {
        // fallback — some PDFs resist appearance updates
    }
}

// Flatten: merge form fields into the page content stream (non-editable)
form.flatten();

const filledBytes = await pdfDoc.save();
writeFileSync(outputPath, filledBytes);
