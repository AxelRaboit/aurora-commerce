import { reactive } from "vue";
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

    const { loading: sectionsSaving, request: sectionsRequest } = useRequest();
    const { loading: itemsSaving, request: itemsRequest } = useRequest();

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

    function sectionLabel(section) {
        return (
            sectionAliases[section.id]?.trim() ||
            t(`backend.nav.sections.${section.id}`)
        );
    }

    function itemDefaultLabel(item) {
        return t(item.labelKey);
    }

    function resetItem(route) {
        delete itemAliases[route];
    }

    function resetAllItems() {
        Object.keys(itemAliases).forEach((route) => delete itemAliases[route]);
    }

    async function saveSections() {
        const data = await sectionsRequest(updatePath, {
            key: "nav_section_aliases",
            value: JSON.stringify(sectionAliases),
        });
        if (data?.success) {
            toast.success(t("backend.settings.saved"));
        }
    }

    async function saveItems() {
        const cleaned = Object.fromEntries(
            Object.entries(itemAliases).filter(
                ([, value]) => typeof value === "string" && value.trim() !== "",
            ),
        );
        const data = await itemsRequest(updatePath, {
            key: "nav_item_aliases",
            value: JSON.stringify(cleaned),
        });
        if (data?.success) {
            toast.success(t("backend.settings.saved"));
        }
    }

    return {
        sectionAliases,
        itemAliases,
        sectionsSaving,
        itemsSaving,
        toggleSection,
        isSectionExpanded,
        sectionLabel,
        itemDefaultLabel,
        resetItem,
        resetAllItems,
        saveSections,
        saveItems,
    };
}
