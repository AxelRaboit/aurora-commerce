/**
 * pdf-lib AcroForm field class names. Use these constants instead of raw strings
 * when matching against `field.constructor.name`.
 */
export const PdfFieldType = Object.freeze({
    TextField: "PDFTextField",
    CheckBox: "PDFCheckBox",
    RadioGroup: "PDFRadioGroup",
    Dropdown: "PDFDropdown",
    OptionList: "PDFOptionList",
    Button: "PDFButton",
});
