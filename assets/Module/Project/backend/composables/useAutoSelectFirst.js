import { watch } from "vue";

export function useAutoSelectFirst(items, activeItem, onSelect) {
    watch(items, (list) => {
        if (list.length > 0 && !activeItem.value) {
            onSelect(list[0]);
        }
    });
}
