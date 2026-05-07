import { createApp, h, ref } from "vue";
import { createAppI18n } from "@/i18n.js";
import MediaPickerModal from "@core/backend/media/MediaPickerModal.vue";

/**
 * Imperative wrapper around <MediaPickerModal>. Mounts the component on
 * demand and resolves with the selected media item (or null if cancelled).
 *
 * Kept as a Promise-returning function so existing callers (AppImagePickerField,
 * EditorBlock, PostFeaturedImagePanel, MediaTextBlock, …) don't have to change.
 */
export function openMediaPicker({ imagesOnly = false, multiple = false } = {}) {
    return new Promise((resolve) => {
        const host = document.createElement("div");
        document.body.appendChild(host);

        const show = ref(true);
        let resolved = false;

        function finish(value) {
            if (resolved) return;
            resolved = true;
            show.value = false;
            setTimeout(() => {
                app.unmount();
                host.remove();
            }, 250);
            resolve(value);
        }

        const locale = document.documentElement.lang?.slice(0, 2) || "fr";

        const app = createApp({
            render() {
                return h(MediaPickerModal, {
                    show: show.value,
                    imagesOnly,
                    multiple,
                    onClose: () => finish(multiple ? [] : null),
                    onSelect: (item) => finish(item),
                });
            },
        });

        app.use(createAppI18n(locale));
        app.mount(host);
    });
}
