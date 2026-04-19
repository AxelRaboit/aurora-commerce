import { mountApp } from "@/utils/mountApp.js";
import RegisterApp from "./RegisterApp.vue";

mountApp("app-register", RegisterApp, (dataset) => ({
    registerPath: dataset.registerPath,
    loginPath: dataset.loginPath,
    registrationEnabled: dataset.registrationEnabled !== "false",
    initialErrors: JSON.parse(dataset.initialErrors || "{}"),
    values: JSON.parse(dataset.values || "{}"),
}));
