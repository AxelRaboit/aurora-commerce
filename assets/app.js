import {
    startStimulusApp,
    registerControllers,
} from "vite-plugin-symfony/stimulus/helpers";
import { registerVueControllerComponents } from "@symfony/ux-vue";
import { createAppI18n } from "@/i18n.js";
import "./css/app.css";

document.addEventListener("vue:before-mount", (event) => {
    const locale = document.documentElement.lang || "fr";
    event.detail.app.use(createAppI18n(locale));
});

const app = startStimulusApp();

registerControllers(
    app,
    import.meta.glob("./controllers/*_controller.js", {
        query: "?stimulus",
        eager: true,
    }),
);

const vueModules = import.meta.glob("./vue/controllers/**/*.vue");
const vueContext = Object.fromEntries(
    Object.entries(vueModules).map(([key, loader]) => [
        key.replace("./vue/controllers/", "./"),
        loader,
    ]),
);
const vueContextFn = (key) => vueContext[key]();
vueContextFn.keys = () => Object.keys(vueContext);
registerVueControllerComponents(vueContextFn);
