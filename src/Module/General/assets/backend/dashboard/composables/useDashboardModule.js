import { computed, ref, watchEffect } from "vue";
import { useI18n } from "vue-i18n";
import {
    Camera,
    FileText,
    Package,
    Receipt,
    ShoppingCart,
    Users,
} from "lucide-vue-next";

const ACTIVE_MODULE_KEY = "aurora-dashboard-module";

const MODULE_DEFINITIONS = [
    {
        id: "editorial",
        labelKey: "backend.nav.sections.editorial",
        icon: FileText,
    },
    { id: "crm", labelKey: "backend.nav.sections.crm", icon: Users },
    { id: "erp", labelKey: "backend.nav.sections.erp", icon: Package },
    { id: "billing", labelKey: "backend.nav.sections.billing", icon: Receipt },
    {
        id: "ecommerce",
        labelKey: "backend.nav.sections.ecommerce",
        icon: ShoppingCart,
    },
    { id: "photo", labelKey: "backend.nav.sections.photo", icon: Camera },
];

export function useDashboardModule(enabledModules) {
    const { t } = useI18n();

    const activeModule = ref(
        localStorage.getItem(ACTIVE_MODULE_KEY) || "editorial",
    );

    const visibleModules = computed(() =>
        MODULE_DEFINITIONS.filter(
            (module) => enabledModules.value[module.id] !== false,
        ).map((module) => ({ ...module, label: () => t(module.labelKey) })),
    );

    function selectModule(id) {
        activeModule.value = id;
        localStorage.setItem(ACTIVE_MODULE_KEY, id);
    }

    watchEffect(() => {
        if (
            visibleModules.value.length > 0 &&
            !visibleModules.value.find((m) => m.id === activeModule.value)
        ) {
            selectModule(visibleModules.value[0].id);
        }
    });

    return { activeModule, selectModule, visibleModules };
}
