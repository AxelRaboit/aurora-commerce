import { ref } from "vue";

export function useAgenciesList(initialAgencies) {
    const agencyList = ref([...initialAgencies]);

    return { agencyList };
}
