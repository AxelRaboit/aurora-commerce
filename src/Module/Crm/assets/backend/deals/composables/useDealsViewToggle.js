import { useUrlSyncedState } from "@/shared/composables/list/useUrlSyncedState.js";

export function useDealsViewToggle(props, onKanbanActivate) {
    const { state: view, set: setView } = useUrlSyncedState({
        initial: props.initialView === "kanban" ? "kanban" : "list",
        serialize: (next) => {
            const path =
                next === "kanban" ? props.kanbanRoutePath : props.listRoutePath;
            if (!path) return null;
            const target = new URL(path, window.location.origin);
            for (const [key, value] of new URLSearchParams(
                window.location.search,
            )) {
                target.searchParams.set(key, value);
            }
            return target;
        },
        deserialize: (event) =>
            event.state?.value ??
            (window.location.pathname.endsWith("/kanban") ? "kanban" : "list"),
        onSync: (next) => {
            if (next === "kanban") onKanbanActivate();
        },
    });

    return { view, setView };
}
