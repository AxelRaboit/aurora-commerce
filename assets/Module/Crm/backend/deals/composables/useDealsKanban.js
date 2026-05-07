import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { toast } from "vue-sonner";

export function useDealsKanban(props) {
    const { t } = useI18n();

    const localColumns = ref(
        props.kanbanColumns
            ? Object.fromEntries(
                  props.stages.map((s) => [
                      s,
                      [...(props.kanbanColumns[s] ?? [])],
                  ]),
              )
            : Object.fromEntries(props.stages.map((s) => [s, []])),
    );
    const kanbanColumnsLoaded = ref(props.kanbanColumns !== null);
    const kanbanLoading = ref(false);
    const activeStage = ref(props.stages[0] ?? "lead");

    async function ensureKanbanColumns(force = false) {
        if (!force && kanbanColumnsLoaded.value) return;
        if (!props.kanbanColumnsPath) return;
        kanbanLoading.value = true;
        try {
            const response = await fetch(props.kanbanColumnsPath, {
                headers: { Accept: "application/json" },
            });
            if (!response.ok) throw new Error();
            const data = await response.json();
            const columns = data.columns ?? {};
            localColumns.value = Object.fromEntries(
                props.stages.map((s) => [s, [...(columns[s] ?? [])]]),
            );
            kanbanColumnsLoaded.value = true;
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            kanbanLoading.value = false;
        }
    }

    const totalByStage = computed(() =>
        Object.fromEntries(
            props.stages.map((s) => [s, localColumns.value[s]?.length ?? 0]),
        ),
    );

    async function patchStage(dealId, stage) {
        try {
            const url = buildPath(props.updateStagePath, { id: dealId });
            const response = await fetch(url, {
                method: HttpMethod.Patch,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ stage }),
            });
            if (!response.ok) return null;
            return await response.json();
        } catch {
            return null;
        }
    }

    async function updateStageForDeal(deal, newStage) {
        if (deal.stage === newStage) return;
        const data = await patchStage(deal.id, newStage);
        if (data?.success) {
            localColumns.value[deal.stage] = localColumns.value[
                deal.stage
            ].filter((d) => d.id !== deal.id);
            deal.stage = newStage;
            localColumns.value[newStage] = [
                ...(localColumns.value[newStage] ?? []),
                deal,
            ];
            activeStage.value = newStage;
        } else {
            toast.error(t("shared.common.error"));
        }
    }

    async function onDrop(event, targetStage) {
        const newIndex = event.newIndex;
        const deal =
            newIndex !== undefined
                ? localColumns.value[targetStage]?.[newIndex]
                : null;
        if (!deal || deal.stage === targetStage) return;

        const previousStage = deal.stage;
        const data = await patchStage(deal.id, targetStage);
        if (data?.success) {
            deal.stage = targetStage;
        } else {
            localColumns.value[targetStage] = localColumns.value[
                targetStage
            ].filter((d) => d.id !== deal.id);
            localColumns.value[previousStage] = [
                ...(localColumns.value[previousStage] ?? []),
                deal,
            ];
            toast.error(t("shared.common.error"));
        }
    }

    return {
        localColumns,
        kanbanColumnsLoaded,
        kanbanLoading,
        activeStage,
        totalByStage,
        ensureKanbanColumns,
        updateStageForDeal,
        onDrop,
    };
}
