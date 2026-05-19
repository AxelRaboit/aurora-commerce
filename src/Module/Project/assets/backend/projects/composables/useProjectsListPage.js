import { ref } from "vue";
import { useListPage } from "@/shared/composables/list/useListPage.js";

export function useProjectsListPage(props) {
    const statusFilter = ref("");

    const {
        items,
        loading: listLoading,
        page,
        totalPages,
        search,
        onSearch,
        goToPage,
        reload,
    } = useListPage(props.listPath, {
        extraParams: () => ({ status: statusFilter.value || undefined }),
    });

    function setStatusFilter(value) {
        statusFilter.value = value;
        reload();
    }

    return {
        items,
        page,
        totalPages,
        search,
        onSearch,
        goToPage,
        reload,
        statusFilter,
        setStatusFilter,
        listLoading,
    };
}
