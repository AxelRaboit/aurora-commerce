import {
    startStimulusApp,
    registerControllers,
} from "vite-plugin-symfony/stimulus/helpers";
import { registerVueControllerComponents } from "@symfony/ux-vue";
import { createAppI18n } from "@/i18n.js";
import "./shared/utils/loader.js";
import "./css/app.css";

document.addEventListener("vue:before-mount", (event) => {
    const locale = document.documentElement.lang || "fr";
    event.detail.app.use(createAppI18n(locale));
});

const app = startStimulusApp();

registerControllers(
    app,
    import.meta.glob("./stimulus/*_controller.js", {
        query: "?stimulus",
        eager: true,
    }),
);

// Core Vue components (backend + frontend cross-cutting), living alongside
// the PHP Aurora\Core\Frontend\ namespace.
const coreModules = import.meta.glob([
    "./backend/**/*.vue",
    "./frontend/**/*.vue",
]);

// All first-party module Vue components (backend + frontend) — auto-discovered.
// Adding a new module under src/Module/<Name>/assets/ requires no change here.
const auroraModules = import.meta.glob("../../Module/*/assets/**/*.vue");

// Optional client extension modules. Resolves via the @client alias which
// points to AURORA_CLIENT_DIR (or an empty fallback when unset). Mirrors
// aurora-core's own layout: components live under
// <client>/src/Module/<Name>/assets/backend/Foo.vue and are exposed as
// ./<name>/backend/Foo.vue so the client uses vue_component('<name>/backend/Foo')
// in Twig — same convention as aurora's first-party modules.
const clientModules = import.meta.glob("@client/src/Module/*/assets/**/*.vue");

// Client overrides: wrappers around Aurora's own Vue components, kept apart
// from feature modules so the prefix doesn't carry a misleading domain
// meaning. A file at @client/src/Overrides/backend/agencies/AgenciesApp.vue
// is exposed as ./backend/agencies/AgenciesApp.vue and accessible via
// vue_component('backend/agencies/AgenciesApp') in Twig — no module prefix.
const clientOverrides = import.meta.glob("@client/src/Overrides/**/*.vue");

const vueContext = {
    // Core: ./backend/Foo.vue → ./core/backend/Foo.vue (./frontend/ likewise)
    ...Object.fromEntries(
        Object.entries(coreModules).map(([key, loader]) => [
            key.replace(/^\.\//, "./core/"),
            loader,
        ]),
    ),
    // Modules: ../../Module/Hr/assets/backend/Foo.vue → ./hr/backend/Foo.vue
    ...Object.fromEntries(
        Object.entries(auroraModules).map(([key, loader]) => {
            const match = key.match(/Module\/([^/]+)\/assets\/(.*)$/);
            if (!match) return [key, loader];
            const [, moduleName, rest] = match;
            return [`./${moduleName.toLowerCase()}/${rest}`, loader];
        }),
    ),
    // Client modules: extract "<ModuleName>/<rest>.vue" and remap to
    // "./<modulename>/<rest>.vue". Same convention as aurora's modules.
    ...Object.fromEntries(
        Object.entries(clientModules).map(([key, loader]) => {
            const match = key.match(/Module\/([^/]+)\/assets\/(.*)$/);
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
