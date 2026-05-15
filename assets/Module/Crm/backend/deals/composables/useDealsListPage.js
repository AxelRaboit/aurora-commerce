import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";

export function useDealsListPage(props) {
    const { t } = useI18n();

    const stageOptions = computed(() =>
        props.stages.map((s) => ({
            value: s,
            label: t(`backend.crm.deals.stages.${s}`),
        })),
    );

    const {
        items,
        loading,
        page,
        totalPages,
        search: searchInput,
        onSearch,
        goToPage,
        reload: reset,
    } = useListPage(props.listPath, {
        initialSearch: props.search,
        initialData: props.deals,
    });

    return {
        stageOptions,
        items,
        loading,
        page,
        totalPages,
        searchInput,
        onSearch,
        goToPage,
        reset,
    };
}
