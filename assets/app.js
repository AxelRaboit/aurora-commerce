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
    "./Core/backend/**/*.vue",
    "./Core/frontend/**/*.vue",
]);
const editorialModules = import.meta.glob([
    "./Module/Editorial/backend/**/*.vue",
    "./Module/Editorial/frontend/**/*.vue",
]);
const crmModules = import.meta.glob("./Module/Crm/backend/**/*.vue");
const erpModules = import.meta.glob("./Module/Erp/backend/**/*.vue");
const ecommerceModules = import.meta.glob([
    "./Module/Ecommerce/backend/**/*.vue",
    "./Module/Ecommerce/frontend/**/*.vue",
]);
const photoModules = import.meta.glob([
    "./Module/Photo/backend/**/*.vue",
    "./Module/Photo/frontend/**/*.vue",
]);
const billingModules = import.meta.glob("./Module/Billing/backend/**/*.vue");
const gedModules = import.meta.glob("./Module/Ged/backend/**/*.vue");
const projectModules = import.meta.glob("./Module/Project/backend/**/*.vue");

// Optional client extension modules. Resolves via the @client alias which
// points to AURORA_CLIENT_DIR (or an empty fallback when unset). Components
// at @client/Module/<Name>/backend/Foo.vue are exposed as ./<name>/backend/Foo.vue
// so the client can use vue_component('<name>/backend/Foo') in Twig — same
// convention as aurora's first-party modules.
const clientModules = import.meta.glob("@client/Module/**/*.vue");

// Client overrides: wrappers around Aurora's own Vue components, kept apart
// from feature modules so the prefix doesn't carry a misleading domain
// meaning. A file at @client/Overrides/backend/agencies/AgenciesApp.vue
// is exposed as ./backend/agencies/AgenciesApp.vue and accessible via
// vue_component('backend/agencies/AgenciesApp') in Twig — no module prefix.
const clientOverrides = import.meta.glob("@client/Overrides/**/*.vue");

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
    ...Object.fromEntries(
        Object.entries(gedModules).map(([key, loader]) => [
            key.replace("./Module/Ged/", "./ged/"),
            loader,
        ]),
    ),
    ...Object.fromEntries(
        Object.entries(projectModules).map(([key, loader]) => [
            key.replace("./Module/Project/", "./project/"),
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
    // Client overrides: extract "<rest>.vue" after "Overrides/" and expose
    // as "./<rest>.vue" with no module prefix.
    ...Object.fromEntries(
        Object.entries(clientOverrides).map(([key, loader]) => {
            const match = key.match(/Overrides\/(.*)$/);
            if (!match) return [key, loader];
            const [, rest] = match;
            return [`./${rest}`, loader];
        }),
    ),
};

const vueContextFn = (key) => vueContext[key]();
vueContextFn.keys = () => Object.keys(vueContext);
registerVueControllerComponents(vueContextFn);
