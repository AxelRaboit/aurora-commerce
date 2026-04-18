import { createApp, h } from "vue";
import { Toaster, toast } from "vue-sonner";
import "vue-sonner/style.css";

document.addEventListener("DOMContentLoaded", () => {
    const toasterContainer = document.createElement("div");
    document.body.appendChild(toasterContainer);
    createApp({
        render: () =>
            h(Toaster, {
                theme: "dark",
                position: "bottom-center",
                richColors: true,
            }),
    }).mount(toasterContainer);

    const flashes = window.__flash__ ?? {};
    for (const [type, messages] of Object.entries(flashes)) {
        for (const message of messages) {
            (toast[type] ?? toast)(message);
        }
    }
});
