export const TEMPLATES = [
    {
        id: "article",
        icon: "📄",
        blocks: [
            { type: "header", data: { text: "", level: 2 } },
            { type: "paragraph", data: { text: "" } },
            {
                type: "image",
                data: {
                    file: { url: "" },
                    caption: "",
                    withBorder: false,
                    stretched: false,
                    withBackground: false,
                },
            },
            { type: "paragraph", data: { text: "" } },
            { type: "paragraph", data: { text: "" } },
        ],
    },
    {
        id: "journal",
        icon: "📰",
        blocks: [
            { type: "header", data: { text: "", level: 2 } },
            {
                type: "mediaText",
                data: { url: "", caption: "", text: "", flip: false },
            },
            { type: "delimiter", data: {} },
            {
                type: "mediaText",
                data: { url: "", caption: "", text: "", flip: true },
            },
        ],
    },
    {
        id: "twoColumn",
        icon: "▥",
        blocks: [
            { type: "header", data: { text: "", level: 2 } },
            { type: "twoColumn", data: { left: "", right: "" } },
        ],
    },
    {
        id: "landing",
        icon: "🚀",
        blocks: [
            { type: "header", data: { text: "", level: 2 } },
            { type: "paragraph", data: { text: "" } },
            {
                type: "image",
                data: {
                    file: { url: "" },
                    caption: "",
                    withBorder: false,
                    stretched: true,
                    withBackground: false,
                },
            },
            { type: "header", data: { text: "", level: 3 } },
            { type: "list", data: { style: "unordered", items: ["", "", ""] } },
            { type: "callout", data: { type: "tip", title: "", message: "" } },
        ],
    },
    {
        id: "tutorial",
        icon: "🛠️",
        blocks: [
            { type: "header", data: { text: "", level: 2 } },
            { type: "paragraph", data: { text: "" } },
            { type: "callout", data: { type: "info", title: "", message: "" } },
            { type: "header", data: { text: "", level: 3 } },
            { type: "paragraph", data: { text: "" } },
            { type: "code", data: { code: "" } },
            { type: "header", data: { text: "", level: 3 } },
            { type: "paragraph", data: { text: "" } },
            { type: "code", data: { code: "" } },
            {
                type: "callout",
                data: { type: "success", title: "", message: "" },
            },
        ],
    },
    {
        id: "newsletter",
        icon: "✉️",
        blocks: [
            { type: "header", data: { text: "", level: 2 } },
            { type: "paragraph", data: { text: "" } },
            { type: "delimiter", data: {} },
            { type: "header", data: { text: "", level: 3 } },
            { type: "list", data: { style: "unordered", items: ["", "", ""] } },
            { type: "delimiter", data: {} },
            { type: "header", data: { text: "", level: 3 } },
            { type: "paragraph", data: { text: "" } },
            { type: "delimiter", data: {} },
            { type: "callout", data: { type: "tip", title: "", message: "" } },
            { type: "delimiter", data: {} },
            { type: "paragraph", data: { text: "" } },
        ],
    },
];
