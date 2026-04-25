export function handlePlainTextPaste(event) {
    event.stopPropagation();
    event.preventDefault();
    const text = event.clipboardData?.getData("text/plain") ?? "";
    if (!text) return;
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) return;
    const range = selection.getRangeAt(0);
    range.deleteContents();
    range.insertNode(document.createTextNode(text));
    range.collapse(false);
    selection.removeAllRanges();
    selection.addRange(range);
    event.target.dispatchEvent(new Event("input", { bubbles: true }));
}
