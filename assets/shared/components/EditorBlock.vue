<script setup>
import { HttpMethod } from "@/shared/utils/httpMethod.js";
import { ref, onMounted, onBeforeUnmount, inject } from "vue";
import { useI18n } from "vue-i18n";
import EditorJS from "@editorjs/editorjs";
import Header from "@editorjs/header";
import List from "@editorjs/list";
import Quote from "@editorjs/quote";
import Code from "@editorjs/code";
import Delimiter from "@editorjs/delimiter";
import Checklist from "@editorjs/checklist";
import Table from "@editorjs/table";
import Embed from "@editorjs/embed";
import Image from "@editorjs/image";
import Marker from "@editorjs/marker";
import InlineCode from "@editorjs/inline-code";
import DragDrop from "editorjs-drag-drop";
import Undo from "editorjs-undo";
import MediaTextBlock from "@/shared/utils/editorjs/MediaTextBlock.js";
import TwoColumnBlock from "@/shared/utils/editorjs/TwoColumnBlock.js";
import CalloutBlock from "@/shared/utils/editorjs/CalloutBlock.js";
import PostsListBlock from "@/shared/utils/editorjs/PostsListBlock.js";
import { configureMediaPickerLabels } from "@/shared/utils/mediaPicker.js";

const { t } = useI18n();

configureMediaPickerLabels({
    title: t("admin.editor.mediaPicker.title"),
    search: t("admin.editor.mediaPicker.search"),
    allFolders: t("admin.editor.mediaPicker.allFolders"),
    empty: t("admin.editor.mediaPicker.empty"),
    loading: t("admin.editor.mediaPicker.loading"),
    cancel: t("shared.common.cancel"),
    select: t("admin.editor.mediaPicker.select"),
});

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    placeholder: { type: String, default: "" },
    uploadUrl: { type: String, default: "/admin/media/upload" },
    postTypes: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:modelValue"]);

const holderEl = ref(null);
const registerFlush  = inject("registerEditorFlush",  null);
const registerRender = inject("registerEditorRender", null);

let editor = null;
let ready = false;
let lastEmittedJson = JSON.stringify(props.modelValue);

function emitIfChanged(blocks) {
    const json = JSON.stringify(blocks);
    if (json === lastEmittedJson) return;
    lastEmittedJson = json;
    emit("update:modelValue", blocks);
}

async function flush() {
    if (editor && ready) {
        const data = await editor.save();
        emitIfChanged(data.blocks);
    }
}

async function renderBlocks(blocks) {
    if (!editor || !ready) return;
    await editor.render({ blocks });
    const data = await editor.save();
    emitIfChanged(data.blocks);
}

onMounted(async () => {
    editor = new EditorJS({
        holder: holderEl.value,
        placeholder: props.placeholder || t("admin.editor.placeholder"),
        data: { blocks: props.modelValue },
        i18n: {
            messages: {
                ui: {
                    blockTunes: {
                        toggler: {
                            "Click to tune":   t("admin.editor.ui.blockTunes.toggler.Click to tune"),
                            "or drag to move": t("admin.editor.ui.blockTunes.toggler.or drag to move"),
                        },
                    },
                    inlineToolbar: {
                        converter: {
                            "Convert to": t("admin.editor.ui.inlineToolbar.converter.Convert to"),
                        },
                    },
                    toolbar: {
                        toolbox: {
                            Add: t("admin.editor.ui.toolbar.toolbox.Add"),
                        },
                    },
                    popover: {
                        Filter:           t("admin.editor.ui.popover.Filter"),
                        "Nothing found":  t("admin.editor.ui.popover.Nothing found"),
                        "Nothing found. Try searching for something else.": t("admin.editor.ui.popover.nothingFoundExtended"),
                    },
                },
                toolNames: {
                    "Text":           t("admin.editor.toolNames.text"),
                    "Heading":        t("admin.editor.toolNames.heading"),
                    "List":           t("admin.editor.toolNames.list"),
                    "Ordered List":   t("admin.editor.toolNames.orderedList"),
                    "Unordered List": t("admin.editor.toolNames.unorderedList"),
                    "Checklist":      t("admin.editor.toolNames.checklist"),
                    "Quote":          t("admin.editor.toolNames.quote"),
                    "Code":           t("admin.editor.toolNames.code"),
                    "Delimiter":      t("admin.editor.toolNames.delimiter"),
                    "Table":          t("admin.editor.toolNames.table"),
                    "Image":          t("admin.editor.toolNames.image"),
                    "Embed":          t("admin.editor.toolNames.embed"),
                    "Marker":         t("admin.editor.toolNames.marker"),
                    "InlineCode":     t("admin.editor.toolNames.inlineCode"),
                    "Callout":        t("admin.editor.toolNames.callout"),
                    "Image + Text":   t("admin.editor.toolNames.mediaText"),
                    "Two Columns":    t("admin.editor.toolNames.twoColumn"),
                    "Liste d'articles": t("admin.editor.toolNames.postsList"),
                },
                blockTunes: {
                    delete: {
                        Delete:           t("admin.editor.blockTunes.delete.Delete"),
                        "Click to delete": t("admin.editor.blockTunes.delete.Click to delete"),
                    },
                    moveUp: {
                        "Move up": t("admin.editor.blockTunes.moveUp.Move up"),
                    },
                    moveDown: {
                        "Move down": t("admin.editor.blockTunes.moveDown.Move down"),
                    },
                },
            },
        },
        tools: {
            // Blocs de texte
            header: {
                class: Header,
                config: { levels: [2, 3, 4], defaultLevel: 2 },
            },
            paragraph: {
                inlineToolbar: true,
            },

            // Listes
            list: {
                class: List,
                inlineToolbar: true,
                config: { defaultStyle: "unordered" },
            },
            checklist: {
                class: Checklist,
                inlineToolbar: true,
            },

            // Médias
            image: {
                class: Image,
                config: {
                    uploader: {
                        uploadByUrl: async (url) => ({ success: 1, file: { url } }),
                        uploadByFile: async (file) => {
                            const body = new FormData();
                            body.append("image", file);
                            try {
                                const response = await fetch(props.uploadUrl, { method: HttpMethod.Post, body });
                                if (!response.ok) return { success: 0 };
                                return response.json();
                            } catch {
                                return { success: 0 };
                            }
                        },
                    },
                    captionPlaceholder: t("admin.editor.image.captionPlaceholder"),
                },
            },
            embed: {
                class: Embed,
                config: {
                    services: {
                        youtube: true,
                        vimeo: true,
                        twitter: true,
                        instagram: true,
                        codepen: true,
                    },
                },
            },
            table: {
                class: Table,
                inlineToolbar: true,
                config: { rows: 2, cols: 3, withHeadings: true },
            },

            // Mise en forme
            quote: {
                class: Quote,
                inlineToolbar: true,
                config: {
                    quotePlaceholder:   t("admin.editor.quote.placeholder"),
                    captionPlaceholder: t("admin.editor.quote.captionPlaceholder"),
                },
            },
            delimiter: Delimiter,
            code: Code,

            // Outils inline
            marker: {
                class: Marker,
            },
            inlineCode: {
                class: InlineCode,
            },

            // Callout
            callout: {
                class: CalloutBlock,
                config: {
                    titlePlaceholder:   t("admin.editor.callout.titlePlaceholder"),
                    messagePlaceholder: t("admin.editor.callout.messagePlaceholder"),
                },
            },

            // Mise en page
            mediaText: {
                class: MediaTextBlock,
                config: {
                    flipLeft:           t("admin.editor.mediaText.flipLeft"),
                    flipRight:          t("admin.editor.mediaText.flipRight"),
                    captionPlaceholder: t("admin.editor.mediaText.captionPlaceholder"),
                    textPlaceholder:    t("admin.editor.mediaText.textPlaceholder"),
                    urlPlaceholder:     t("admin.editor.mediaText.urlPlaceholder"),
                    changeUrl:          t("admin.editor.mediaText.changeUrl"),
                    confirm:            t("admin.editor.mediaText.confirm"),
                    browse:             t("admin.editor.mediaText.browse"),
                    orLabel:            t("admin.editor.mediaText.or"),
                    mediaIdPlaceholder: t("admin.editor.mediaText.mediaIdPlaceholder"),
                    mediaIdNotFound:    t("admin.editor.mediaText.mediaIdNotFound"),
                },
            },
            twoColumn: {
                class: TwoColumnBlock,
            },
            postsList: {
                class: PostsListBlock,
                config: {
                    postTypes: props.postTypes,
                    titleLabel: t("admin.editor.postsList.titleLabel"),
                    postTypeLabel: t("admin.editor.postsList.postTypeLabel"),
                    columnsLabel: t("admin.editor.postsList.columnsLabel"),
                    modeLabel: t("admin.editor.postsList.modeLabel"),
                    modeAutoLabel: t("admin.editor.postsList.modeAutoLabel"),
                    modeManualLabel: t("admin.editor.postsList.modeManualLabel"),
                    perPageLabel: t("admin.editor.postsList.perPageLabel"),
                    searchPlaceholderLabel: t("admin.editor.postsList.searchPlaceholderLabel"),
                    selectedLabel: t("admin.editor.postsList.selectedLabel"),
                    emptyLabel: t("admin.editor.postsList.emptyLabel"),
                    noResultsLabel: t("admin.editor.postsList.noResultsLabel"),
                },
            },
        },
        onChange: async () => {
            if (!editor) return;
            const data = await editor.save();
            emitIfChanged(data.blocks);
        },
    });
    const localEditor = editor;
    try {
        await localEditor.isReady;
    } catch {
        return;
    }
    if (editor !== localEditor) return;
    new DragDrop(localEditor);
    new Undo({ editor: localEditor });
    ready = true;
    registerFlush?.(flush);
    registerRender?.(renderBlocks);
});

onBeforeUnmount(async () => {
    registerFlush?.(null);
    registerRender?.(null);
    await flush();
    if (editor && ready) editor.destroy();
    editor = null;
    ready = false;
});
</script>

<template>
    <div ref="holderEl" class="editor-block-holder" />
</template>
