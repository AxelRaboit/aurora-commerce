import {
    startStimulusApp,
    registerControllers,
} from "vite-plugin-symfony/stimulus/helpers";
import { registerVueControllerComponents } from "@symfony/ux-vue";
import { createAppI18n } from "@/i18n.js";
import "@/Module/Ecommerce/scripts/cartBadge.js";
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

const coreModules = import.meta.glob("./Core/vue/controllers/**/*.vue");
const editorialModules = import.meta.glob(
    "./Module/Editorial/vue/controllers/**/*.vue",
);
const crmModules = import.meta.glob("./Module/Crm/vue/controllers/**/*.vue");
const erpModules = import.meta.glob("./Module/Erp/vue/controllers/**/*.vue");
const ecommerceModules = import.meta.glob(
    "./Module/Ecommerce/vue/controllers/**/*.vue",
);
const photoModules = import.meta.glob(
    "./Module/Photo/vue/controllers/**/*.vue",
);
const billingModules = import.meta.glob(
    "./Module/Billing/vue/controllers/**/*.vue",
);

const vueContext = {
    ...Object.fromEntries(
        Object.entries(coreModules).map(([key, loader]) => [
            key.replace("./Core/vue/controllers/", "./core/"),
            loader,
        ]),
    ),
    ...Object.fromEntries(
        Object.entries(editorialModules).map(([key, loader]) => [
            key.replace("./Module/Editorial/vue/controllers/", "./editorial/"),
            loader,
        ]),
    ),
    ...Object.fromEntries(
        Object.entries(crmModules).map(([key, loader]) => [
            key.replace("./Module/Crm/vue/controllers/", "./crm/"),
            loader,
        ]),
    ),
    ...Object.fromEntries(
        Object.entries(erpModules).map(([key, loader]) => [
            key.replace("./Module/Erp/vue/controllers/", "./erp/"),
            loader,
        ]),
    ),
    ...Object.fromEntries(
        Object.entries(ecommerceModules).map(([key, loader]) => [
            key.replace("./Module/Ecommerce/vue/controllers/", "./ecommerce/"),
            loader,
        ]),
    ),
    ...Object.fromEntries(
        Object.entries(photoModules).map(([key, loader]) => [
            key.replace("./Module/Photo/vue/controllers/", "./photo/"),
            loader,
        ]),
    ),
    ...Object.fromEntries(
        Object.entries(billingModules).map(([key, loader]) => [
            key.replace("./Module/Billing/vue/controllers/", "./billing/"),
            loader,
        ]),
    ),
};

const vueContextFn = (key) => vueContext[key]();
vueContextFn.keys = () => Object.keys(vueContext);
registerVueControllerComponents(vueContextFn);
