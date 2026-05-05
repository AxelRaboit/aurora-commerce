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

// Optional client extension modules. Resolves via the @client alias which
// points to AURORA_CLIENT_DIR (or an empty fallback when unset). Components
// at @client/Module/<Name>/admin/Foo.vue are exposed as ./<name>/admin/Foo.vue
// so the client can use vue_component('<name>/admin/Foo') in Twig — same
// convention as aurora's first-party modules.
const clientModules = import.meta.glob("@client/Module/**/*.vue");

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
    // Client modules: extract "<ModuleName>/<rest>.vue" from any key shape and
    // remap to "./<modulename>/<rest>.vue". Works regardless of how Vite
    // normalises alias paths in the returned glob keys.
    ...Object.fromEntries(
        Object.entries(clientModules).map(([key, loader]) => {
            const match = key.match(/Module\/([^/]+)\/(.*)$/);
            if (!match) return [key, loader];
            const [, moduleName, rest] = match;
            return [`./${moduleName.toLowerCase()}/${rest}`, loader];
        }),
    ),
};

const vueContextFn = (key) => vueContext[key]();
vueContextFn.keys = () => Object.keys(vueContext);
registerVueControllerComponents(vueContextFn);
