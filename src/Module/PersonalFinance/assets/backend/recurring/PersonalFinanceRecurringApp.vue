<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { Plus, Pencil, Trash2, Save, X, RotateCw, Play, Pause, CheckCircle2, Clock } from "lucide-vue-next";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppAmountInput from "@/shared/components/form/input/AppAmountInput.vue";
import AppDatePicker from "@/shared/components/form/picker/AppDatePicker.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { useRecurringForm } from "./composables/useRecurringForm.js";
import { useScheduledForm } from "./composables/useScheduledForm.js";
import { useRecurringToggle } from "./composables/useRecurringToggle.js";
import { useScheduledMaterialize } from "./composables/useScheduledMaterialize.js";

const props = defineProps({
    wallets: { type: Array, required: true },
    categoriesByWallet: { type: Object, required: true },
    types: { type: Array, required: true },
    recurring: { type: Array, required: true },
    scheduled: { type: Array, required: true },
    createRecurringPath: { type: String, required: true },
    updateRecurringPath: { type: String, required: true },
    toggleRecurringPath: { type: String, required: true },
    deleteRecurringPath: { type: String, required: true },
    createScheduledPath: { type: String, required: true },
    updateScheduledPath: { type: String, required: true },
    materializeScheduledPath: { type: String, required: true },
    deleteScheduledPath: { type: String, required: true },
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const tab = ref("recurring");
const recurring = ref([...props.recurring]);
const scheduled = ref([...props.scheduled]);

const walletOptions = computed(() => props.wallets.map((w) => ({ value: w.id, label: w.name })));
const typeOptions = computed(() => props.types.map((ty) => ({ value: ty, label: t(`personal_finance.transactions.types.${ty}`) })));

function categoryOptionsForWallet(walletId) {
    const opts = [{ value: null, label: t("personal_finance.transactions.uncategorized") }];
    if (!walletId) return opts;
    const cats = props.categoriesByWallet[String(walletId)] ?? [];
    for (const c of cats) opts.push({ value: c.id, label: c.name });
    return opts;
}

function refreshRecurring(updated) {
    if (!updated) return;
    const idx = recurring.value.findIndex((r) => r.id === updated.id);
    if (idx >= 0) recurring.value[idx] = updated;
    else recurring.value.push(updated);
}

function refreshScheduled(updated) {
    if (!updated) return;
    const idx = scheduled.value.findIndex((s) => s.id === updated.id);
    if (idx >= 0) scheduled.value[idx] = updated;
    else scheduled.value.push(updated);
}

const {
    show: showRec,
    isEditing: recEditing,
    form: recForm,
    errors: recErrors,
    loading: recLoading,
    openCreate: openRecCreate,
    openEdit: openRecEdit,
    submit: submitRec,
} = useRecurringForm(props.createRecurringPath, props.updateRecurringPath, refreshRecurring);

const {
    show: showSched,
    isEditing: schedEditing,
    form: schedForm,
    errors: schedErrors,
    loading: schedLoading,
    openCreate: openSchedCreate,
    openEdit: openSchedEdit,
    submit: submitSched,
} = useScheduledForm(props.createScheduledPath, props.updateScheduledPath, refreshScheduled);

const { pendingDelete: pendingDeleteRec, loading: deleteRecLoading, confirm: confirmDeleteRec, submit: doDeleteRec } = useDelete(
    props.deleteRecurringPath,
    (id) => { recurring.value = recurring.value.filter((r) => r.id !== id); },
    "personal_finance.recurring.deleted_recurring",
);

const { pendingDelete: pendingDeleteSched, loading: deleteSchedLoading, confirm: confirmDeleteSched, submit: doDeleteSched } = useDelete(
    props.deleteScheduledPath,
    (id) => { scheduled.value = scheduled.value.filter((s) => s.id !== id); },
    "personal_finance.recurring.deleted_scheduled",
);

const { toggle: toggleRec } = useRecurringToggle(props.toggleRecurringPath, refreshRecurring);
const { materialize } = useScheduledMaterialize(props.materializeScheduledPath, refreshScheduled);

function formatType(type) {
    return t(`personal_finance.transactions.types.${type}`);
}

function signedAmount(row) {
    const sign = row.type === "income" ? "+" : "-";
    return `${sign}${row.amount}`;
}
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <div class="flex border-b border-line">
                <button
                    type="button"
                    class="px-4 py-2 text-sm border-b-2 transition-colors"
                    :class="tab === 'recurring' ? 'border-accent-500 text-primary' : 'border-transparent text-muted hover:text-primary'"
                    v-on:click="tab = 'recurring'"
                >
                    <RotateCw class="inline w-3.5 h-3.5 mr-1" :stroke-width="2" />
                    {{ t("personal_finance.recurring.tabs.recurring") }}
                </button>
                <button
                    type="button"
                    class="px-4 py-2 text-sm border-b-2 transition-colors"
                    :class="tab === 'scheduled' ? 'border-accent-500 text-primary' : 'border-transparent text-muted hover:text-primary'"
                    v-on:click="tab = 'scheduled'"
                >
                    <Clock class="inline w-3.5 h-3.5 mr-1" :stroke-width="2" />
                    {{ t("personal_finance.recurring.tabs.scheduled") }}
                </button>
            </div>
            <template #actions>
                <AppButton v-if="tab === 'recurring'" variant="primary" size="md" class="w-full sm:w-auto" :disabled="!wallets.length" v-on:click="openRecCreate">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t("personal_finance.recurring.add_recurring") }}
                </AppButton>
                <AppButton v-else variant="primary" size="md" class="w-full sm:w-auto" :disabled="!wallets.length" v-on:click="openSchedCreate">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t("personal_finance.recurring.add_scheduled") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <!-- Recurring tab -->
        <template v-if="tab === 'recurring'">
            <section v-if="!recurring.length" class="bg-surface border border-line rounded-lg p-6 text-muted text-sm">
                {{ t("personal_finance.recurring.empty_recurring") }}
            </section>

            <div v-else class="sm:hidden space-y-3">
                <div v-for="rec in recurring" :key="rec.id" class="bg-surface border border-line rounded-lg p-4 space-y-2" :class="rec.active ? '' : 'opacity-50'">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-primary truncate">{{ rec.description ?? '—' }}</p>
                            <p class="text-xs text-muted mt-0.5">{{ rec.categoryName ?? t("personal_finance.transactions.uncategorized") }}</p>
                            <p class="text-xs text-muted">{{ rec.walletName }} · {{ t("personal_finance.dashboard.day_of_month", { day: rec.dayOfMonth }) }}</p>
                            <p v-if="rec.nextExpectedDate" class="text-xs text-muted">{{ t("personal_finance.recurring.next_expected", { date: formatDateShort(rec.nextExpectedDate) }) }}</p>
                        </div>
                        <p class="font-mono text-sm shrink-0" :class="rec.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ signedAmount(rec) }}</p>
                    </div>
                    <slot name="extra-cells" :tab="'recurring'" :item="rec" />
                    <div class="flex items-center justify-end gap-0.5 pt-2 border-t border-line">
                        <AppIconButton :color="rec.active ? 'amber' : 'emerald'" :title="rec.active ? t('personal_finance.recurring.toggle_disable') : t('personal_finance.recurring.toggle_enable')" v-on:click="toggleRec(rec)">
                            <Pause v-if="rec.active" class="w-4 h-4" :stroke-width="2" />
                            <Play v-else class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openRecEdit(rec)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDeleteRec(rec)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>
            </div>

            <div v-if="recurring.length" class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.recurring.fields.description") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.recurring.fields.wallet") }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.recurring.fields.amount") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.recurring.fields.day_of_month") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.recurring.fields.next_expected") }}</th>
                            <slot name="extra-headers" :tab="'recurring'" />
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-for="rec in recurring" :key="rec.id" :class="rec.active ? '' : 'opacity-50'">
                            <td class="px-6 py-3">
                                {{ rec.description ?? '—' }}
                                <p class="text-xs text-muted">{{ rec.categoryName ?? t("personal_finance.transactions.uncategorized") }}</p>
                            </td>
                            <td class="px-6 py-3">{{ rec.walletName }}</td>
                            <td class="px-6 py-3 text-right font-mono" :class="rec.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ signedAmount(rec) }}</td>
                            <td class="px-6 py-3">{{ rec.dayOfMonth }}</td>
                            <td class="px-6 py-3 text-xs">
                                <span v-if="rec.nextExpectedDate">{{ formatDateShort(rec.nextExpectedDate) }}</span>
                                <span v-else class="text-muted">—</span>
                            </td>
                            <slot name="extra-cells" :tab="'recurring'" :item="rec" />
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppIconButton :color="rec.active ? 'amber' : 'emerald'" :title="rec.active ? t('personal_finance.recurring.toggle_disable') : t('personal_finance.recurring.toggle_enable')" v-on:click="toggleRec(rec)">
                                        <Pause v-if="rec.active" class="w-4 h-4" :stroke-width="2" />
                                        <Play v-else class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openRecEdit(rec)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDeleteRec(rec)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <!-- Scheduled tab -->
        <template v-else>
            <section v-if="!scheduled.length" class="bg-surface border border-line rounded-lg p-6 text-muted text-sm">
                {{ t("personal_finance.recurring.empty_scheduled") }}
            </section>

            <div v-else class="sm:hidden space-y-3">
                <div v-for="sched in scheduled" :key="sched.id" class="bg-surface border border-line rounded-lg p-4 space-y-2" :class="sched.generated ? 'opacity-60' : ''">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-primary truncate">{{ sched.description ?? '—' }}</p>
                            <p class="text-xs text-muted mt-0.5">{{ sched.categoryName ?? t("personal_finance.transactions.uncategorized") }}</p>
                            <p class="text-xs text-muted">{{ sched.walletName }} · {{ formatDateShort(sched.scheduledDate) }}</p>
                            <p class="text-xs mt-1">
                                <span v-if="sched.generated" class="inline-flex items-center gap-1 text-emerald-400">
                                    <CheckCircle2 class="w-3.5 h-3.5" :stroke-width="2" />
                                    {{ t("personal_finance.recurring.status_generated") }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 text-amber-400">
                                    <Clock class="w-3.5 h-3.5" :stroke-width="2" />
                                    {{ t("personal_finance.recurring.status_pending") }}
                                </span>
                            </p>
                        </div>
                        <p class="font-mono text-sm shrink-0" :class="sched.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ signedAmount(sched) }}</p>
                    </div>
                    <slot name="extra-cells" :tab="'scheduled'" :item="sched" />
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-line">
                        <AppButton v-if="!sched.generated" variant="ghost" size="sm" v-on:click="materialize(sched)">
                            <CheckCircle2 class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("personal_finance.recurring.materialize") }}
                        </AppButton>
                        <AppIconButton v-if="!sched.generated" color="accent" :title="t('shared.common.edit')" v-on:click="openSchedEdit(sched)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDeleteSched(sched)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>
            </div>

            <div v-if="scheduled.length" class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.recurring.fields.description") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.recurring.fields.wallet") }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.recurring.fields.amount") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.recurring.fields.scheduled_date") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.status") }}</th>
                            <slot name="extra-headers" :tab="'scheduled'" />
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-for="sched in scheduled" :key="sched.id" :class="sched.generated ? 'opacity-60' : ''">
                            <td class="px-6 py-3">
                                {{ sched.description ?? '—' }}
                                <p class="text-xs text-muted">{{ sched.categoryName ?? t("personal_finance.transactions.uncategorized") }}</p>
                            </td>
                            <td class="px-6 py-3">{{ sched.walletName }}</td>
                            <td class="px-6 py-3 text-right font-mono" :class="sched.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ signedAmount(sched) }}</td>
                            <td class="px-6 py-3 text-xs">{{ formatDateShort(sched.scheduledDate) }}</td>
                            <td class="px-6 py-3">
                                <span v-if="sched.generated" class="inline-flex items-center gap-1 text-xs text-emerald-400">
                                    <CheckCircle2 class="w-3.5 h-3.5" :stroke-width="2" />
                                    {{ t("personal_finance.recurring.status_generated") }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 text-xs text-amber-400">
                                    <Clock class="w-3.5 h-3.5" :stroke-width="2" />
                                    {{ t("personal_finance.recurring.status_pending") }}
                                </span>
                            </td>
                            <slot name="extra-cells" :tab="'scheduled'" :item="sched" />
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppButton v-if="!sched.generated" variant="ghost" size="sm" v-on:click="materialize(sched)">
                                        <CheckCircle2 class="w-3.5 h-3.5" :stroke-width="2" />
                                        {{ t("personal_finance.recurring.materialize") }}
                                    </AppButton>
                                    <AppIconButton v-if="!sched.generated" color="accent" :title="t('shared.common.edit')" v-on:click="openSchedEdit(sched)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDeleteSched(sched)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <!-- Recurring modal -->
        <AppModal :show="showRec" :title="recEditing ? t('personal_finance.recurring.edit_recurring') : t('personal_finance.recurring.add_recurring')" :icon="RotateCw" :closeable="false" v-on:close="showRec = false">
            <form class="space-y-4" v-on:submit.prevent="submitRec">
                <AppMultiselect v-model="recForm.walletId" :label="t('personal_finance.recurring.fields.wallet')" :options="walletOptions" :allow-empty="false" :error="recErrors.walletId" required />
                <AppMultiselect v-model="recForm.type" :label="t('personal_finance.recurring.fields.type')" :options="typeOptions" :allow-empty="false" required />
                <AppAmountInput v-model="recForm.amount" :label="t('personal_finance.recurring.fields.amount')" :placeholder="t('personal_finance.transactions.placeholders.amount')" :error="recErrors.amount" required />
                <AppMultiselect v-model="recForm.categoryId" :label="t('personal_finance.recurring.fields.category')" :options="categoryOptionsForWallet(recForm.walletId)" :allow-empty="true" />
                <AppInput v-model="recForm.description" :label="t('personal_finance.recurring.fields.description')" />
                <div>
                    <AppInput v-model.number="recForm.dayOfMonth" type="number" min="1" max="28" :label="t('personal_finance.recurring.fields.day_of_month')" :error="recErrors.dayOfMonth" required />
                    <p class="text-xs text-muted mt-1">{{ t("personal_finance.recurring.fields.day_of_month_hint") }}</p>
                </div>
                <slot name="extra-form-fields" :form="recForm" :errors="recErrors" :editing="recEditing" :tab="'recurring'" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showRec = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="recLoading" v-on:click="submitRec">
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Scheduled modal -->
        <AppModal :show="showSched" :title="schedEditing ? t('personal_finance.recurring.edit_scheduled') : t('personal_finance.recurring.add_scheduled')" :icon="Clock" :closeable="false" v-on:close="showSched = false">
            <form class="space-y-4" v-on:submit.prevent="submitSched">
                <AppMultiselect v-model="schedForm.walletId" :label="t('personal_finance.recurring.fields.wallet')" :options="walletOptions" :allow-empty="false" :error="schedErrors.walletId" required />
                <AppMultiselect v-model="schedForm.type" :label="t('personal_finance.recurring.fields.type')" :options="typeOptions" :allow-empty="false" required />
                <AppAmountInput v-model="schedForm.amount" :label="t('personal_finance.recurring.fields.amount')" :placeholder="t('personal_finance.transactions.placeholders.amount')" :error="schedErrors.amount" required />
                <AppMultiselect v-model="schedForm.categoryId" :label="t('personal_finance.recurring.fields.category')" :options="categoryOptionsForWallet(schedForm.walletId)" :allow-empty="true" />
                <AppDatePicker v-model="schedForm.scheduledDate" :label="t('personal_finance.recurring.fields.scheduled_date')" :placeholder="t('personal_finance.transactions.placeholders.date')" :error="schedErrors.scheduledDate" required />
                <AppInput v-model="schedForm.description" :label="t('personal_finance.recurring.fields.description')" />
                <slot name="extra-form-fields" :form="schedForm" :errors="schedErrors" :editing="schedEditing" :tab="'scheduled'" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showSched = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="schedLoading" v-on:click="submitSched">
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Delete confirmations -->
        <AppModal :show="!!pendingDeleteRec" max-width="sm" :closeable="false" :title="t('shared.common.delete')" :icon="Trash2" v-on:close="pendingDeleteRec = null">
            <p class="text-sm text-primary">{{ t("personal_finance.recurring.delete_confirm_recurring") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDeleteRec = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteRecLoading" v-on:click="doDeleteRec">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal :show="!!pendingDeleteSched" max-width="sm" :closeable="false" :title="t('shared.common.delete')" :icon="Trash2" v-on:close="pendingDeleteSched = null">
            <p class="text-sm text-primary">{{ t("personal_finance.recurring.delete_confirm_scheduled") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDeleteSched = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteSchedLoading" v-on:click="doDeleteSched">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
