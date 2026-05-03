export const isMac = typeof navigator !== "undefined" && /Mac|iP(hone|od|ad)/.test(navigator.platform);

export const modKeyLabel = isMac ? "⌘" : "Ctrl";
