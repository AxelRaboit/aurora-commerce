<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { VueDraggable } from "vue-draggable-plus";
import { List, Columns2 } from "lucide-vue-next";
import AppSelect from "@/shared/components/AppSelect.vue";
import { useApiRequest } from "@/shared/composables/useApiRequest.js";
import { HttpMethod } from "@/shared/utils/httpMethod.js";
import { toast } from "vue-sonner";

const { t } = useI18n();

const props = defineProps({
    columns: { type: Object, default: () => ({}) },
    stages: { type: Array, default: () => [] },
    updateStagePath: { type: String, required: true },
    listPath: { type: String, default: "" },
});

function goToList() { location.href = props.listPath; }

const { request } = useApiRequest();

const activeStage = ref(props.stages[0] ?? 'lead');

const localColumns = ref(
    Object.fromEntries(props.stages.map(s => [s, [...(props.columns[s] ?? [])]]))
);

const stageBadge = (stage) => ({
    lead: "bg-slate-500/15 text-slate-400 border-slate-500/30",
    qualified: "bg-blue-500/15 text-blue-400 border-blue-500/30",
    proposal: "bg-violet-500/15 text-violet-400 border-violet-500/30",
    negotiation: "bg-amber-500/15 text-amber-400 border-amber-500/30",
    won: "bg-emerald-500/15 text-emerald-400 border-emerald-500/30",
    lost: "bg-red-500/15 text-red-400 border-red-500/30",
}[stage] ?? "bg-slate-500/15 text-slate-400 border-slate-500/30");

const totalByStage = computed(() =>
    Object.fromEntries(props.stages.map(s => [s, localColumns.value[s]?.length ?? 0]))
);

async function updateStageForDeal(deal, newStage) {
    if (deal.stage === newStage) return;
    const url = props.updateStagePath.replace("__id__", deal.id);
    const data = await request(url, { stage: newStage }, HttpMethod.Patch);
    if (data?.success) {
        // Move card between columns
        localColumns.value[deal.stage] = localColumns.value[deal.stage].filter(d => d.id !== deal.id);
        deal.stage = newStage;
        localColumns.value[newStage] = [...(localColumns.value[newStage] ?? []), deal];
        activeStage.value = newStage;
    }
}

async function onDrop(event, targetStage) {
    const deal = event.item?.__draggable_context?.element;
    if (!deal || deal.stage === targetStage) return;

    const previousStage = deal.stage;
    const url = props.updateStagePath.replace("__id__", deal.id);
    const data = await request(url, { stage: targetStage }, HttpMethod.Patch);
    if (data?.success) {
        deal.stage = targetStage;
    } else {
        // Revert: move the card back to its previous column
        localColumns.value[targetStage] = localColumns.value[targetStage].filter(d => d.id !== deal.id);
        localColumns.value[previousStage] = [...(localColumns.value[previousStage] ?? []), deal];
        toast.error(t("shared.common.error"));
    }
}
</script>

<template>
    <!-- View tabs -->
    <div class="flex border-b border-line/40 text-sm mb-4">
        <button type="button" class="px-4 py-2 border-b-2 border-transparent text-muted hover:text-secondary font-medium transition-colors flex items-center gap-1.5" v-on:click="goToList">
            <List class="w-4 h-4" :stroke-width="2" />
            {{ t('admin.crm.deals.listView') }}
        </button>
        <button type="button" class="px-4 py-2 border-b-2 border-accent-500 text-primary font-medium transition-colors flex items-center gap-1.5">
            <Columns2 class="w-4 h-4" :stroke-width="2" />
            {{ t('admin.crm.deals.kanbanView') }}
        </button>
    </div>

    <!-- Mobile: tabs by stage -->
    <div class="sm:hidden">
        <!-- Stage tabs (scrollable pill buttons) -->
        <div class="flex overflow-x-auto gap-1.5 pb-2 scrollbar-thin">
            <button
                v-for="stage in stages"
                :key="stage"
                type="button"
                :class="['shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all', activeStage === stage ? stageBadge(stage) : 'bg-surface-2 text-muted border-transparent hover:text-primary']"
                v-on:click="activeStage = stage"
            >
                {{ t(`admin.crm.deals.stages.${stage}`) }}
                <span :class="['inline-flex items-center justify-center min-w-4 h-4 rounded-full px-1 text-xs', activeStage === stage ? 'bg-white/20' : 'bg-surface-3 text-muted']">
                    {{ localColumns[stage]?.length ?? 0 }}
                </span>
            </button>
        </div>

        <!-- Cards of active stage -->
        <div class="mt-3 space-y-2">
            <p v-if="!localColumns[activeStage]?.length" class="py-8 text-center text-sm text-muted">
                {{ t('admin.crm.deals.empty') }}
            </p>
            <div
                v-for="deal in localColumns[activeStage]"
                :key="deal.id"
                class="bg-surface border border-line rounded-lg p-4 space-y-2"
            >
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium text-primary">{{ deal.name }}</p>
                    <AppSelect
                        :model-value="deal.stage"
                        class="shrink-0"
                        v-on:update:model-value="updateStageForDeal(deal, $event)"
                    >
                        <option v-for="s in stages" :key="s" :value="s">{{ t(`admin.crm.deals.stages.${s}`) }}</option>
                    </AppSelect>
                </div>
                <div class="flex items-center justify-between">
                    <span v-if="deal.contact || deal.company" class="text-xs text-muted truncate">
                        {{ deal.contact?.fullName ?? deal.company?.name }}
                    </span>
                    <span v-if="deal.value" class="text-xs font-semibold text-secondary ml-auto shrink-0">
                        {{ Number(deal.value).toLocaleString() }} €
                    </span>
                </div>
                <p v-if="deal.closingDate" class="text-xs text-muted">{{ deal.closingDate }}</p>
            </div>
        </div>
    </div>

    <!-- Desktop: kanban columns -->
    <div class="hidden sm:flex gap-4 overflow-x-auto pb-4 min-h-[calc(100vh-16rem)]">
        <div
            v-for="stage in stages"
            :key="stage"
            class="shrink-0 w-72 flex flex-col gap-2"
        >
            <div :class="['flex items-center justify-between px-3 py-2 rounded-lg border text-xs font-semibold uppercase tracking-wide', stageBadge(stage)]">
                <span>{{ t(`admin.crm.deals.stages.${stage}`) }}</span>
                <span class="text-xs opacity-70">{{ totalByStage[stage] }}</span>
            </div>
            <VueDraggable
                v-model="localColumns[stage]"
                :group="{ name: 'deals', put: true, pull: true }"
                :animation="150"
                class="flex flex-col gap-2 min-h-12 flex-1"
                v-on:add="(e) => onDrop(e, stage)"
            >
                <div
                    v-for="deal in localColumns[stage]"
                    :key="deal.id"
                    class="bg-surface border border-line rounded-lg p-3 cursor-grab active:cursor-grabbing hover:border-accent-500/40 transition-colors select-none"
                >
                    <p class="text-sm font-medium text-primary leading-snug mb-1">{{ deal.name }}</p>
                    <div class="flex items-center justify-between">
                        <span v-if="deal.contact || deal.company" class="text-xs text-muted truncate">
                            {{ deal.contact?.fullName ?? deal.company?.name }}
                        </span>
                        <span v-if="deal.value" class="text-xs font-medium text-secondary ml-auto shrink-0">
                            {{ Number(deal.value).toLocaleString() }} €
                        </span>
                    </div>
                    <p v-if="deal.closingDate" class="text-xs text-muted mt-1">{{ deal.closingDate }}</p>
                </div>
            </VueDraggable>
        </div>
    </div>
</template>
