<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useDetailDelete } from "@/shared/composables/form/useDetailDelete.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppStagePicker from "@/shared/components/nav/AppStagePicker.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppDatePicker from "@/shared/components/form/AppDatePicker.vue";
import { Pencil, Trash2, Save, X } from "lucide-vue-next";
import { required } from "@/shared/utils/validation/validators.js";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { stageBadge } from "@crm/backend/utils/deals/stageStyles.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

const { t } = useI18n();

const props = defineProps({
    deal: { type: Object, required: true },
    backPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    updateStagePath: { type: String, required: true },
});

const STAGES = ['lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
const stageOptions = STAGES.map(s => ({ value: s, label: t(`backend.crm.deals.stages.${s}`) }));

const stageLabel = (stage) => t(`backend.crm.deals.stages.${stage}`);

const deal = ref({ ...props.deal });

// Stage update directly
const currentStage = ref(deal.value.stage);
const { loading: stageLoading, request: stageRequest } = useApiRequest();

async function updateStage(newStage) {
    const data = await stageRequest(props.updateStagePath, { stage: newStage }, HttpMethod.Patch);
    if (data?.success) {
        currentStage.value = newStage;
        deal.value.stage = newStage;
        toast.success(t('backend.crm.deals.updated'));
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
    if (!validateEdit({ name: () => required(t("backend.crm.deals.errors.name_required"))(editForm.value.name) })) return;
    const data = await editRequest(props.updatePath, editForm.value);
    if (!data) return;
    if (data.success) {
        deal.value = { ...deal.value, ...(data.deal ?? editForm.value) };
        currentStage.value = deal.value.stage;
        showEdit.value = false;
        toast.success(t('backend.crm.deals.updated'));
    } else {
        setEditErrors(translateServerErrors(t, data.errors));
    }
}

// Delete
const { showDelete, loading: deleteLoading, submit: doDelete } = useDetailDelete(props.deletePath, props.backPath);
</script>

<template>
    <div class="max-w-2xl space-y-6">
        <div class="bg-surface border border-line rounded-lg p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                <div class="min-w-0">
                    <h2 class="text-lg sm:text-xl font-bold text-primary break-words">{{ deal.name }}</h2>
                    <span :class="['inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-medium', stageBadge(currentStage)]">
                        {{ t(`backend.crm.deals.stages.${currentStage}`) }}
                    </span>
                </div>
                <div class="flex items-center gap-1 sm:gap-2 sm:shrink-0 self-end sm:self-auto">
                    <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="showEdit = true"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="showDelete = true"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                </div>
            </div>

            <dl class="space-y-3">
                <div v-if="deal.contact || deal.company">
                    <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.crm.deals.contact') }}</dt>
                    <dd class="text-primary text-sm">{{ deal.contact?.fullName ?? deal.company?.name }}</dd>
                </div>
                <div v-if="deal.value">
                    <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.crm.deals.value') }}</dt>
                    <dd class="text-primary text-sm font-medium">{{ Number(deal.value).toLocaleString() }} €</dd>
                </div>
                <div v-if="deal.closingDate">
                    <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.crm.deals.closingDate') }}</dt>
                    <dd class="text-secondary text-sm">{{ deal.closingDate }}</dd>
                </div>
                <div v-if="deal.notes">
                    <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.crm.contacts.notes') }}</dt>
                    <dd class="text-secondary text-sm whitespace-pre-wrap">{{ deal.notes }}</dd>
                </div>
            </dl>

            <div class="mt-6 pt-4 border-t border-line">
                <p class="text-xs text-muted uppercase tracking-wide mb-3">{{ t('backend.crm.deals.stage') }}</p>
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

        <AppModal
            :show="showEdit"
            :title="t('backend.crm.deals.edit', { name: deal.name })"
            :icon="Pencil"
            :closeable="false"
            v-on:close="showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('backend.crm.deals.name')"
                    :placeholder="t('backend.crm.deals.namePlaceholder')"
                    :error="editErrors.name"
                    required
                />
                <AppSelect v-model="editForm.stage" :label="t('backend.crm.deals.stage')">
                    <option v-for="opt in stageOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </AppSelect>
                <AppInput v-model="editForm.value" :label="t('backend.crm.deals.value')" :placeholder="t('backend.crm.deals.valuePlaceholder')" />
                <AppDatePicker v-model="editForm.closingDate" :label="t('backend.crm.deals.closingDate')" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal :show="showDelete" max-width="sm" :closeable="false" v-on:close="showDelete = false">
            <p class="text-sm text-primary">{{ t('backend.crm.deals.deleteConfirm', { name: deal.name }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.crm.deals.deleteWarning') }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="showDelete = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
