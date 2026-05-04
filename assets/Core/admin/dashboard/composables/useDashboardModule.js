import { ref } from "vue";

const ACTIVE_MODULE_KEY = "aurora-dashboard-module";

export function useDashboardModule() {
    const activeModule = ref(
        localStorage.getItem(ACTIVE_MODULE_KEY) || "editorial",
    );

    function selectModule(id) {
        activeModule.value = id;
        localStorage.setItem(ACTIVE_MODULE_KEY, id);
    }

    return { activeModule, selectModule };
}
