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

const coreModules = import.meta.glob([
    "./Core/admin/**/*.vue",
    "./Core/front/**/*.vue",
]);
const editorialModules = import.meta.glob([
    "./Module/Editorial/admin/**/*.vue",
    "./Module/Editorial/front/**/*.vue",
]);
const crmModules = import.meta.glob("./Module/Crm/admin/**/*.vue");
const erpModules = import.meta.glob("./Module/Erp/admin/**/*.vue");
const ecommerceModules = import.meta.glob([
    "./Module/Ecommerce/admin/**/*.vue",
    "./Module/Ecommerce/front/**/*.vue",
]);
const photoModules = import.meta.glob([
    "./Module/Photo/admin/**/*.vue",
    "./Module/Photo/front/**/*.vue",
]);
const billingModules = import.meta.glob("./Module/Billing/admin/**/*.vue");

const vueContext = {
    ...Object.fromEntries(
        Object.entries(coreModules).map(([key, loader]) => [
            key.replace("./Core/", "./core/"),
            loader,
        ]),
    ),
    ...Object.fromEntries(
        Object.entries(editorialModules).map(([key, loader]) => [
            key.replace("./Module/Editorial/", "./editorial/"),
            loader,
        ]),
    ),
    ...Object.fromEntries(
        Object.entries(crmModules).map(([key, loader]) => [
            key.replace("./Module/Crm/", "./crm/"),
            loader,
        ]),
    ),
    ...Object.fromEntries(
        Object.entries(erpModules).map(([key, loader]) => [
            key.replace("./Module/Erp/", "./erp/"),
            loader,
        ]),
    ),
    ...Object.fromEntries(
        Object.entries(ecommerceModules).map(([key, loader]) => [
            key.replace("./Module/Ecommerce/", "./ecommerce/"),
            loader,
        ]),
    ),
    ...Object.fromEntries(
        Object.entries(photoModules).map(([key, loader]) => [
            key.replace("./Module/Photo/", "./photo/"),
            loader,
        ]),
    ),
    ...Object.fromEntries(
        Object.entries(billingModules).map(([key, loader]) => [
            key.replace("./Module/Billing/", "./billing/"),
            loader,
        ]),
    ),
};

const vueContextFn = (key) => vueContext[key]();
vueContextFn.keys = () => Object.keys(vueContext);
registerVueControllerComponents(vueContextFn);
