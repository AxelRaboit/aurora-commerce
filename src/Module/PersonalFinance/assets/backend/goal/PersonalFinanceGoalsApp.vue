<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { Plus, Pencil, Trash2, Save, X, Target, PiggyBank, Zap } from "lucide-vue-next";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppAmountInput from "@/shared/components/form/input/AppAmountInput.vue";
import AppDatePicker from "@/shared/components/form/picker/AppDatePicker.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppColorPicker from "@/shared/components/form/picker/AppColorPicker.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { useGoalsForm } from "./composables/useGoalsForm.js";
import { useGoalDeposit } from "./composables/useGoalDeposit.js";
import { useGoalsSort, monthlyContribution } from "./composables/useGoalsSort.js";

const props = defineProps({
    goals: { type: Array, required: true },
    wallets: { type: Array, required: true },
    categoriesByWallet: { type: Object, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    depositPath: { type: String, required: true },
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const goals = ref([...props.goals]);
const { sortBy, sortOptions, sortedGoals } = useGoalsSort(goals);

const walletOptions = computed(() => [
    { value: null, label: t("personal_finance.goals.no_wallet_link") },
    ...props.wallets.map((w) => ({ value: w.id, label: w.name })),
]);

const categoryOptionsForForm = computed(() => {
    const opts = [{ value: null, label: t("personal_finance.goals.no_category_link") }];
    for (const wallet of props.wallets) {
        const cats = props.categoriesByWallet[String(wallet.id)] ?? [];
        for (const c of cats) {
            opts.push({ value: c.id, label: `${wallet.name} — ${c.name}` });
        }
    }
    return opts;
});

function refreshGoal(updated) {
    if (!updated) return;
    const idx = goals.value.findIndex((g) => g.id === updated.id);
    if (idx >= 0) {
        goals.value[idx] = updated;
    } else {
        goals.value.push(updated);
    }
}

const {
    show: showForm,
    isEditing: formEditing,
    form,
    errors: formErrors,
    loading: formLoading,
    openCreate,
    openEdit,
    submit: submitForm,
} = useGoalsForm(props.createPath, props.updatePath, refreshGoal, { extraFields: props.extraFields });

const {
    show: showDeposit,
    target: depositTarget,
    amount: depositAmount,
    errors: depositErrors,
    loading: depositLoading,
    open: openDeposit,
    submit: submitDeposit,
} = useGoalDeposit(props.depositPath, refreshGoal);

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath,
    (id) => {
        goals.value = goals.value.filter((g) => g.id !== id);
    },
    "personal_finance.goals.deleted",
);

</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <AppMultiselect
                v-model="sortBy"
                :options="sortOptions"
                :allow-empty="false"
                class="w-full sm:w-64"
            />
            <template #actions>
                <slot name="extra-headers" />
                <AppButton variant="primary" size="md" class="w-full sm:w-auto" v-on:click="openCreate">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t("personal_finance.goals.add") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <AppMessage variant="info">
            {{ t("personal_finance.goals.help") }}
        </AppMessage>

        <section v-if="!sortedGoals.length" class="bg-surface border border-line rounded-lg p-8 text-center text-muted text-sm">
            {{ t("personal_finance.goals.empty") }}
        </section>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <article
                v-for="goal in sortedGoals"
                :key="goal.id"
                class="bg-surface border border-line rounded-lg p-4 flex flex-col gap-3"
            >
                <header class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-block w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: goal.color ?? '#6366f1' }"></span>
                        <h3 class="text-base font-medium text-primary truncate">{{ goal.name }}</h3>
                    </div>
                    <div class="flex items-center gap-0.5">
                        <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(goal)">
                            <Pencil class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(goal)">
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                </header>

                <div>
                    <div class="flex items-baseline justify-between text-sm">
                        <span class="font-mono text-primary">{{ goal.savedAmount }} / {{ goal.targetAmount }}</span>
                        <span class="text-muted">{{ goal.progress }} %</span>
                    </div>
                    <div class="w-full h-2 bg-line/40 rounded mt-1 overflow-hidden">
                        <div class="h-full" :style="{ width: goal.progress + '%', backgroundColor: goal.color ?? '#6366f1' }"></div>
                    </div>
                </div>

                <dl class="text-xs text-muted space-y-0.5">
                    <div v-if="goal.deadline">
                        <span class="font-medium">{{ t("personal_finance.goals.fields.deadline") }}:</span>
                        {{ formatDateShort(goal.deadline) }}
                        <span v-if="monthlyContribution(goal)" class="ml-1 text-accent">
                            → {{ t("personal_finance.goals.monthly_contribution", { amount: monthlyContribution(goal) }) }}
                        </span>
                    </div>
                    <div v-if="goal.categoryName">
                        <Zap class="inline w-3 h-3" :stroke-width="2" />
                        {{ t("personal_finance.goals.auto_tracked_from", { category: goal.categoryName }) }}
                    </div>
                    <div v-else-if="goal.walletName">
                        {{ t("personal_finance.goals.fields.wallet") }}: {{ goal.walletName }}
                    </div>
                </dl>

                <slot name="extra-cells" :goal="goal" />

                <footer class="pt-1 border-t border-line/40">
                    <AppButton
                        v-if="!goal.isAutoTracked"
                        variant="ghost"
                        size="sm"
                        :disabled="goal.isCompleted"
                        v-on:click="openDeposit(goal)"
                    >
                        <PiggyBank class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("personal_finance.goals.deposit") }}
                    </AppButton>
                    <span v-else class="text-xs text-muted">{{ t("personal_finance.goals.deposit_disabled") }}</span>
                </footer>
            </article>
        </div>

        <AppModal
            :show="showForm"
            :title="formEditing ? t('personal_finance.goals.edit') : t('personal_finance.goals.add')"
            :icon="Target"
            :closeable="false"
            v-on:close="showForm = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitForm">
                <AppInput
                    v-model="form.name"
                    :label="t('personal_finance.goals.fields.name')"
                    :placeholder="t('personal_finance.goals.placeholders.name')"
                    :error="formErrors.name"
                    required
                />
                <AppAmountInput
                    v-model="form.targetAmount"
                    :label="t('personal_finance.goals.fields.target')"
                    :placeholder="t('personal_finance.transactions.placeholders.amount')"
                    :error="formErrors.targetAmount"
                    required
                />
                <AppDatePicker
                    v-model="form.deadline"
                    :label="t('personal_finance.goals.fields.deadline')"
                    :placeholder="t('personal_finance.transactions.placeholders.date')"
                />
                <AppMultiselect
                    v-model="form.walletId"
                    :label="t('personal_finance.goals.fields.wallet')"
                    :options="walletOptions"
                    :allow-empty="true"
                />
                <AppMultiselect
                    v-model="form.categoryId"
                    :label="t('personal_finance.goals.fields.category')"
                    :options="categoryOptionsForForm"
                    :allow-empty="true"
                />
                <p v-if="form.categoryId" class="text-xs text-muted">{{ t("personal_finance.goals.auto_tracked_hint") }}</p>
                <AppColorPicker
                    v-model="form.color"
                    :label="t('personal_finance.goals.fields.color')"
                />
                <slot name="extra-form-fields" :form="form" :errors="formErrors" :editing="formEditing" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showForm = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="formLoading" v-on:click="submitForm">
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="showDeposit"
            :title="t('personal_finance.goals.deposit_title', { name: depositTarget?.name ?? '' })"
            :icon="PiggyBank"
            :closeable="false"
            v-on:close="showDeposit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitDeposit">
                <p class="text-sm text-muted">{{ t("personal_finance.goals.deposit_hint") }}</p>
                <AppAmountInput
                    v-model="depositAmount"
                    :label="t('personal_finance.goals.fields.deposit_amount')"
                    :placeholder="t('personal_finance.transactions.placeholders.amount')"
                    :error="depositErrors.amount"
                    required
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showDeposit = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="depositLoading" v-on:click="submitDeposit">
                        <PiggyBank class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("personal_finance.goals.deposit") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">
                {{ t("personal_finance.goals.delete_confirm", { name: pendingDelete?.name ?? '' }) }}
            </p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
