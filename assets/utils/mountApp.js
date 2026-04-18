import { createApp } from "vue";
import { createAppI18n } from "@/i18n.js";

export function mountApp(elementId, component, transformProps) {
    const mountElement = document.getElementById(elementId);
    if (mountElement) {
        const locale = mountElement.dataset.locale || "fr";
        let props;

        if (transformProps) {
            props = transformProps(mountElement.dataset);
        } else {
            props = {};
            for (const [key, value] of Object.entries(mountElement.dataset)) {
                if (value === "true" || value === "false") {
                    props[key] = value === "true";
                } else if (!isNaN(value) && value !== "") {
                    props[key] = Number(value);
                } else {
                    props[key] = value;
                }
            }
        }

        createApp(component, props)
            .use(createAppI18n(locale))
            .mount(mountElement);
    }
}
