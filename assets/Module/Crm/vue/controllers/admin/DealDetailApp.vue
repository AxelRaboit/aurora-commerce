<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useApiRequest } from "@/shared/composables/useApiRequest.js";
import { useForm } from "@/shared/composables/useForm.js";
import AppButton from "@/shared/components/AppButton.vue";
import AppIconButton from "@/shared/components/AppIconButton.vue";
import AppStagePicker from "@/shared/components/AppStagePicker.vue";
import AppInput from "@/shared/components/AppInput.vue";
import AppSelect from "@/shared/components/AppSelect.vue";
import AppModal from "@/shared/components/AppModal.vue";
import AppModalFooter from "@/shared/components/AppModalFooter.vue";
import AppDatePicker from "@/shared/components/AppDatePicker.vue";
import { Pencil, Trash2 } from "lucide-vue-next";
import { required } from "@/shared/utils/validators.js";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/httpMethod.js";

const { t } = useI18n();

const props = defineProps({
    deal: { type: Object, required: true },
    backPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    updateStagePath: { type: String, required: true },
});

const STAGES = ['lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
const stageOptions = STAGES.map(s => ({ value: s, label: t(`admin.crm.deals.stages.${s}`) }));

const stageLabel = (stage) => t(`admin.crm.deals.stages.${stage}`);

const stageBadge = (stage) => ({
    lead: "bg-slate-500/15 text-slate-400",
    qualified: "bg-blue-500/15 text-blue-400",
    proposal: "bg-violet-500/15 text-violet-400",
    negotiation: "bg-amber-500/15 text-amber-400",
    won: "bg-emerald-500/15 text-emerald-400",
    lost: "bg-red-500/15 text-red-400",
}[stage] ?? "bg-slate-500/15 text-slate-400");

const deal = ref({ ...props.deal });

// Stage update directly
const currentStage = ref(deal.value.stage);
const { loading: stageLoading, request: stageRequest } = useApiRequest();

async function updateStage(newStage) {
    const data = await stageRequest(props.updateStagePath, { stage: newStage }, HttpMethod.Patch);
    if (data?.success) {
        currentStage.value = newStage;
        deal.value.stage = newStage;
        toast.success(t('admin.crm.deals.updated'));
    }
}

// Edit
const showEdit = ref(false);
const editForm = ref({
    name: deal.value.name,
    stage: deal.value.stage,
    value: deal.value.value ?? "",
    closingDate: deal.value.closingDate ?? "",
    notes: deal.value.notes ?? "",
});
const { errors: editErrors, validate: validateEdit, clearErrors: clearEdit, setErrors: setEditErrors } = useForm();
const { loading: editLoading, request: editRequest } = useApiRequest();

async function submitEdit() {
    if (!validateEdit({ name: () => required(t("admin.crm.deals.errors.name_required"))(editForm.value.name) })) return;
    const data = await editRequest(props.updatePath, editForm.value);
    if (!data) return;
    if (data.success) {
        deal.value = { ...deal.value, ...(data.deal ?? editForm.value) };
        currentStage.value = deal.value.stage;
        showEdit.value = false;
        toast.success(t('admin.crm.deals.updated'));
    } else {
        setEditErrors(data.errors ?? {});
    }
}

// Delete
const showDelete = ref(false);
const { loading: deleteLoading, request: deleteRequest } = useApiRequest();
async function doDelete() {
    const data = await deleteRequest(props.deletePath, {});
    if (data?.success) window.location.href = props.backPath;
}
</script>

<template>
    <div class="max-w-2xl space-y-6">
        <!-- Deal card -->
        <div class="bg-surface border border-line rounded-lg p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                <div class="min-w-0">
                    <h2 class="text-lg sm:text-xl font-bold text-primary break-words">{{ deal.name }}</h2>
                    <span :class="['inline-flex items-center mt-1 px-2 py-0.5 rounded text-xs font-medium', stageBadge(currentStage)]">
                        {{ t(`admin.crm.deals.stages.${currentStage}`) }}
                    </span>
                </div>
                <div class="flex items-center gap-1 sm:gap-2 sm:shrink-0 self-end sm:self-auto">
                    <AppIconButton color="indigo" :title="t('shared.common.edit')" v-on:click="showEdit = true"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="showDelete = true"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                </div>
            </div>

            <dl class="space-y-3">
                <div v-if="deal.contact || deal.company">
                    <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('admin.crm.deals.contact') }}</dt>
                    <dd class="text-primary text-sm">{{ deal.contact?.fullName ?? deal.company?.name }}</dd>
                </div>
                <div v-if="deal.value">
                    <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('admin.crm.deals.value') }}</dt>
                    <dd class="text-primary text-sm font-medium">{{ Number(deal.value).toLocaleString() }} €</dd>
                </div>
                <div v-if="deal.closingDate">
                    <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('admin.crm.deals.closingDate') }}</dt>
                    <dd class="text-secondary text-sm">{{ deal.closingDate }}</dd>
                </div>
                <div v-if="deal.notes">
                    <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('admin.crm.contacts.notes') }}</dt>
                    <dd class="text-secondary text-sm whitespace-pre-wrap">{{ deal.notes }}</dd>
                </div>
            </dl>

            <!-- Stage quick-change -->
            <div class="mt-6 pt-4 border-t border-line">
                <p class="text-xs text-muted uppercase tracking-wide mb-3">{{ t('admin.crm.deals.stage') }}</p>
                <AppStagePicker
                    :model-value="currentStage"
                    :stages="STAGES"
                    :label-fn="stageLabel"
                    :badge-fn="stageBadge"
                    :disabled="stageLoading"
                    v-on:update:model-value="updateStage"
                />
            </div>
        </div>

        <!-- Edit modal -->
        <AppModal :show="showEdit" v-on:close="showEdit = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.crm.deals.edit', { name: deal.name }) }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('admin.crm.deals.name')"
                    :placeholder="t('admin.crm.deals.namePlaceholder')"
                    :error="editErrors.name"
                    required
                />
                <AppSelect v-model="editForm.stage" :label="t('admin.crm.deals.stage')">
                    <option v-for="opt in stageOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </AppSelect>
                <AppInput v-model="editForm.value" :label="t('admin.crm.deals.value')" :placeholder="t('admin.crm.deals.valuePlaceholder')" />
                <AppDatePicker v-model="editForm.closingDate" :label="t('admin.crm.deals.closingDate')" />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading">{{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <!-- Delete confirm -->
        <AppModal :show="showDelete" max-width="sm" v-on:close="showDelete = false">
            <p class="text-sm text-primary">{{ t('admin.crm.deals.deleteConfirm', { name: deal.name }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.crm.deals.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="showDelete = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
