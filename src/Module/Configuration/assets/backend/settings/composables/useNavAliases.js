import { reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

function parseJsonMap(raw) {
    try {
        const decoded = JSON.parse(raw ?? "{}");
        return decoded && typeof decoded === "object" && !Array.isArray(decoded)
            ? decoded
            : {};
    } catch {
        return {};
    }
}

function findGroupValue(groups, key) {
    return groups?.navigation?.find?.((s) => s.key === key)?.value ?? "{}";
}

export function useNavAliases({ groups, updatePath }) {
    const { t } = useI18n();

    const sectionAliases = reactive(
        parseJsonMap(findGroupValue(groups, "nav_section_aliases")),
    );
    const itemAliases = reactive(
        parseJsonMap(findGroupValue(groups, "nav_item_aliases")),
    );
    const expandedSections = reactive(new Set());

    const { request } = useRequest();
    const saving = ref(false);

    function toggleSection(sectionId) {
        if (expandedSections.has(sectionId)) {
            expandedSections.delete(sectionId);
        } else {
            expandedSections.add(sectionId);
        }
    }

    function isSectionExpanded(sectionId) {
        return expandedSections.has(sectionId);
    }

    function sectionDefaultLabel(section) {
        return t(`backend.nav.sections.${section.id}`);
    }

    function sectionLabel(section) {
        return (
            sectionAliases[section.id]?.trim() || sectionDefaultLabel(section)
        );
    }

    function itemDefaultLabel(item) {
        return t(item.labelKey);
    }

    function resetItem(route) {
        delete itemAliases[route];
    }

    function resetSection(sectionId) {
        delete sectionAliases[sectionId];
    }

    function resetAll() {
        Object.keys(sectionAliases).forEach((id) => delete sectionAliases[id]);
        Object.keys(itemAliases).forEach((route) => delete itemAliases[route]);
    }

    async function saveAll() {
        if (saving.value) return;

        const cleanedSections = Object.fromEntries(
            Object.entries(sectionAliases).filter(
                ([, value]) => typeof value === "string" && value.trim() !== "",
            ),
        );
        const cleanedItems = Object.fromEntries(
            Object.entries(itemAliases).filter(
                ([, value]) => typeof value === "string" && value.trim() !== "",
            ),
        );

        saving.value = true;
        try {
            const [sectionsRes, itemsRes] = await Promise.all([
                request(
                    updatePath,
                    {
                        key: "nav_section_aliases",
                        value: JSON.stringify(cleanedSections),
                    },
                    { noGuard: true },
                ),
                request(
                    updatePath,
                    {
                        key: "nav_item_aliases",
                        value: JSON.stringify(cleanedItems),
                    },
                    { noGuard: true },
                ),
            ]);

            if (sectionsRes?.success && itemsRes?.success) {
                toast.success(t("backend.settings.saved"));
            }
        } finally {
            saving.value = false;
        }
    }

    return {
        sectionAliases,
        itemAliases,
        saving,
        toggleSection,
        isSectionExpanded,
        sectionDefaultLabel,
        sectionLabel,
        itemDefaultLabel,
        resetItem,
        resetSection,
        resetAll,
        saveAll,
    };
}
