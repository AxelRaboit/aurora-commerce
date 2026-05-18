import { ref } from "vue";

export function useServicesList(initialServices) {
    const serviceList = ref([...initialServices]);

    return { serviceList };
}
