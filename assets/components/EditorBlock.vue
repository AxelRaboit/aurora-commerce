<script setup>
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
import MediaTextBlock from "@/utils/editorjs/MediaTextBlock.js";
import TwoColumnBlock from "@/utils/editorjs/TwoColumnBlock.js";
import CalloutBlock from "@/utils/editorjs/CalloutBlock.js";

const { t } = useI18n();

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    placeholder: { type: String, default: "" },
    uploadUrl: { type: String, default: "/admin/media/upload" },
});

const emit = defineEmits(["update:modelValue"]);

const holderEl = ref(null);
const registerFlush = inject("registerEditorFlush", null);

let editor = null;
let ready = false;

async function flush() {
    if (editor && ready) {
        const data = await editor.save();
        emit("update:modelValue", data.blocks);
    }
}

onMounted(async () => {
    editor = new EditorJS({
        holder: holderEl.value,
        placeholder: props.placeholder || t("admin.editor.placeholder"),
        data: { blocks: props.modelValue },
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
                                const response = await fetch(props.uploadUrl, { method: "POST", body });
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
                    types: [
                        { value: "info",    label: t("admin.editor.callout.types.info"),    icon: "ℹ️" },
                        { value: "success", label: t("admin.editor.callout.types.success"), icon: "✅" },
                        { value: "warning", label: t("admin.editor.callout.types.warning"), icon: "⚠️" },
                        { value: "danger",  label: t("admin.editor.callout.types.danger"),  icon: "🚨" },
                        { value: "tip",     label: t("admin.editor.callout.types.tip"),     icon: "💡" },
                    ],
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
                },
            },
            twoColumn: {
                class: TwoColumnBlock,
            },
        },
        onChange: async () => {
            if (!editor) return;
            const data = await editor.save();
            emit("update:modelValue", data.blocks);
        },
    });
    await editor.isReady;
    new DragDrop(editor);
    new Undo({ editor });
    ready = true;
    registerFlush?.(flush);
});

onBeforeUnmount(async () => {
    registerFlush?.(null);
    await flush();
    if (editor && ready) editor.destroy();
    editor = null;
    ready = false;
});
</script>

<template>
    <div ref="holderEl" class="editor-block-holder" />
</template>
