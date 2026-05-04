import { ref, onMounted, onBeforeUnmount } from "vue";

// `selected` is passed in as a shared ref (also used by useMediaPickerData for reset after reload)
export function useMediaPickerSelection({
    show,
    multiple,
    emit,
    selectFolder,
    foldersOpenMobile,
    selected,
}) {
    const multiSelected = ref([]);

    function isSelected(item) {
        if (multiple) return multiSelected.value.some((m) => m.id === item.id);
        return selected.value?.id === item.id;
    }

    function pick(item) {
        if (multiple) {
            const idx = multiSelected.value.findIndex((m) => m.id === item.id);
            if (idx >= 0) multiSelected.value.splice(idx, 1);
            else multiSelected.value.push(item);
            return;
        }
        selected.value = item;
    }

    function confirm() {
        if (multiple) {
            if (multiSelected.value.length > 0)
                emit("select", [...multiSelected.value]);
            return;
        }
        if (selected.value) emit("select", selected.value);
    }

    function close() {
        emit("close");
    }

    function onKey(event) {
        if (!show) return;
        if (event.key === "Escape") {
            event.preventDefault();
            close();
            return;
        }
        if (
            event.key === "Enter" &&
            selected.value &&
            document.activeElement?.tagName !== "INPUT"
        ) {
            event.preventDefault();
            confirm();
        }
    }

    onMounted(() => document.addEventListener("keydown", onKey));
    onBeforeUnmount(() => document.removeEventListener("keydown", onKey));

    function pickFolderMobile(id) {
        selectFolder(id);
        foldersOpenMobile.value = false;
    }

    return {
        multiSelected,
        isSelected,
        pick,
        confirm,
        close,
        pickFolderMobile,
    };
}
