// Boot hook (auto-run by app.js's *.register.js glob): registers the Assistant
// settings tab Vue with the core settings registry — so aurora-core's
// tabRegistry.js no longer imports an Assistant component. Mirrors the PHP side,
// where the Assistant module declares its ConfigurationTab.
import { registerSettingsTabComponent } from "@configuration/backend/settings/tabRegistry.js";
import AssistantSettingsTab from "./AssistantSettingsTab.vue";

registerSettingsTabComponent("assistant-settings", AssistantSettingsTab);
