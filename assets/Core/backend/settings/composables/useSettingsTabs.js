import { useI18n } from "vue-i18n";
import { useTabState } from "@/shared/composables/useTabState.js";

const GROUP_ORDER = [
    "general",
    "reading",
    "localization",
    "branding",
    "appearance",
    "seo",
    "system",
    "email",
    "sequences",
    "navigation",
];

// Groups rendered by a custom UI (not the generic parameter renderer). They
// must show even when the backend reports no parameters for them.
const ALWAYS_VISIBLE_GROUPS = new Set(["navigation", "appearance"]);

export function useSettingsTabs(groups) {
    const { t } = useI18n();

    const availableGroups = GROUP_ORDER.filter(
        (groupName) =>
            groups?.[groupName] || ALWAYS_VISIBLE_GROUPS.has(groupName),
    );

    const { activeTab, select: selectTab } = useTabState(availableGroups, {
        storageKey: "aurora-settings-active-tab",
    });

    function tabLabel(groupName) {
        return t(`backend.settings.tabs.${groupName}`);
    }

    function tabDescription(groupName) {
        return t(`backend.settings.tabs.${groupName}_description`);
    }

    return {
        availableGroups,
        activeTab,
        selectTab,
        tabLabel,
        tabDescription,
    };
}
