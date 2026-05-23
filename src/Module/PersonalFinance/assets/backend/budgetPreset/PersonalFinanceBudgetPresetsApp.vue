<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { Plus, Pencil, Trash2, Save, X, ClipboardList, Play, Receipt } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppAmountInput from "@/shared/components/form/input/AppAmountInput.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useBudgetPresetsForm } from "./composables/useBudgetPresetsForm.js";
import { useBudgetPresetApply } from "./composables/useBudgetPresetApply.js";



const props = defineProps({
    wallets: { type: Array, required: true },
    categoriesByWallet: { type: Object, required: true },
    selectedWalletId: { type: Number, default: null },
    presets: { type: Array, default: () => [] },
    sections: { type: Array, required: true },
    applyModes: { type: Array, required: true },
    listPath: { type: String, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    applyPath: { type: String, required: true },
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const { request } = useRequest();

const selectedWalletId = ref(props.selectedWalletId ?? props.wallets[0]?.id ?? null);
const presets = ref([...props.presets]);

const walletOptions = computed(() => props.wallets.map((w) => ({ value: w.id, label: w.name })));

const currentCategoryOptions = computed(() => {
    if (!selectedWalletId.value) return [{ value: null, label: t("personal_finance.budget.no_category") }];
    const list = props.categoriesByWallet[String(selectedWalletId.value)] ?? [];
    return [
        { value: null, label: t("personal_finance.budget.no_category") },
        ...list.map((c) => ({ value: c.id, label: c.name })),
    ];
});

const sectionOptions = computed(() =>
    props.sections.map((s) => ({
        value: s,
        label: t(`personal_finance.budget.sections.${s}`),
    })),
);

async function refetchPresets() {
    if (!selectedWalletId.value) {
        presets.value = [];
        return;
    }
    const url = buildPath(props.listPath, { walletId: selectedWalletId.value });
    const response = await request(url, undefined, { method: "GET" });
    if (response?.success !== false) {
        presets.value = response?.presets ?? [];
    }
}

watch(selectedWalletId, () => refetchPresets());

function refreshPreset(updated) {
    if (!updated) return;
    const idx = presets.value.findIndex((p) => p.id === updated.id);
    if (idx >= 0) {
        presets.value[idx] = updated;
    } else {
        presets.value.push(updated);
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
    addItem,
    removeItem,
    submit: submitForm,
} = useBudgetPresetsForm(props.createPath, props.updatePath, refreshPreset);

const {
    show: showApply,
    target: applyTarget,
    month: applyMonth,
    mode: applyMode,
    loading: applyLoading,
    open: openApply,
    submit: submitApply,
} = useBudgetPresetApply(props.applyPath, () => {});

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath,
    (id) => {
        presets.value = presets.value.filter((p) => p.id !== id);
    },
    "personal_finance.budget_presets.deleted",
);

function summarizeItems(preset) {
    return t("personal_finance.budget_presets.summary", { count: preset.itemCount ?? 0 });
}
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <AppMultiselect
                v-model="selectedWalletId"
                :label="t('personal_finance.budget_presets.wallet_selector')"
                :options="walletOptions"
                :allow-empty="false"
                class="min-w-[14rem]"
            />
            <template #actions>
                <AppButton
                    variant="primary"
                    size="md"
                    :disabled="!selectedWalletId"
                    v-on:click="openCreate(selectedWalletId, sections[3] || 'expenses')"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t("personal_finance.budget_presets.add") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <AppMessage variant="info">
            {{ t("personal_finance.budget_presets.help") }}
        </AppMessage>

        <section v-if="!wallets.length" class="bg-surface border border-line rounded-lg p-6 text-muted text-sm">
            {{ t("personal_finance.budget_presets.no_wallet") }}
        </section>

        <section v-else-if="!presets.length" class="bg-surface border border-line rounded-lg p-8 text-center">
            <ClipboardList class="w-10 h-10 mx-auto text-muted/60 mb-3" :stroke-width="1.5" />
            <p class="text-sm text-muted">{{ t("personal_finance.budget_presets.empty") }}</p>
        </section>

        <ul v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <li
                v-for="preset in presets"
                :key="preset.id"
                class="bg-surface border border-line rounded-lg p-4 flex flex-col gap-3"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h3 class="text-base font-medium text-primary truncate">{{ preset.name }}</h3>
                        <p v-if="preset.description" class="text-xs text-muted mt-1 line-clamp-2">{{ preset.description }}</p>
                        <p class="text-xs text-muted mt-1">{{ summarizeItems(preset) }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <AppButton variant="primary" size="sm" v-on:click="openApply(preset)">
                        <Play class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("personal_finance.budget_presets.apply") }}
                    </AppButton>
                    <AppIconButton :title="t('shared.common.edit')" v-on:click="openEdit(preset)">
                        <Pencil class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <AppIconButton variant="danger" :title="t('shared.common.delete')" v-on:click="confirmDelete(preset)">
                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                </div>
            </li>
        </ul>

        <!-- Create / edit modal -->
        <AppModal
            :show="showForm"
            :title="formEditing ? t('personal_finance.budget_presets.edit') : t('personal_finance.budget_presets.create')"
            :icon="ClipboardList"
            :closeable="false"
            max-width="lg"
            v-on:close="showForm = false"
        >
            <div class="space-y-4">
                <AppInput
                    v-model="form.name"
                    :label="`${t('personal_finance.budget_presets.fields.name')} *`"
                    :placeholder="t('personal_finance.budget_presets.placeholders.name')"
                    :error="formErrors.name"
                />
                <AppInput
                    v-model="form.description"
                    :label="t('personal_finance.budget_presets.fields.description')"
                    :placeholder="t('personal_finance.budget_presets.placeholders.description')"
                    :error="formErrors.description"
                />

                <div class="flex items-center justify-between border-t border-line pt-3">
                    <span class="text-sm font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.budget_presets.items_title") }}</span>
                    <AppButton variant="ghost" size="sm" v-on:click="addItem('expenses')">
                        <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                        <span class="hidden sm:inline">{{ t("personal_finance.budget.add_item") }}</span>
                    </AppButton>
                </div>

                <ul v-if="form.items.length" class="space-y-2">
                    <li
                        v-for="(item, idx) in form.items"
                        :key="idx"
                        class="bg-surface-2 border border-line rounded-md p-3 grid grid-cols-1 sm:grid-cols-12 gap-2 items-end"
                    >
                        <AppMultiselect
                            v-model="item.section"
                            :label="`${t('personal_finance.budget.fields.section')} *`"
                            :options="sectionOptions"
                            :allow-empty="false"
                            class="sm:col-span-3"
                        />
                        <AppInput
                            v-model="item.label"
                            :label="`${t('personal_finance.budget.fields.label')} *`"
                            :placeholder="t('personal_finance.budget.placeholders.label')"
                            class="sm:col-span-3"
                        />
                        <AppMultiselect
                            v-model="item.categoryId"
                            :label="t('personal_finance.budget.fields.category')"
                            :options="currentCategoryOptions"
                            class="sm:col-span-3"
                        />
                        <AppAmountInput
                            v-model="item.plannedAmount"
                            :label="`${t('personal_finance.budget.fields.planned')} *`"
                            :placeholder="t('personal_finance.budget.placeholders.planned')"
                            class="sm:col-span-2"
                        />
                        <div class="sm:col-span-1 flex justify-end">
                            <AppIconButton
                                variant="danger"
                                :title="t('shared.common.delete')"
                                v-on:click="removeItem(idx)"
                            >
                                <Trash2 class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted text-center py-4">{{ t("personal_finance.budget_presets.no_items") }}</p>
            </div>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" v-on:click="showForm = false">
                        <X class="w-4 h-4" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" :loading="formLoading" v-on:click="submitForm">
                        <Save class="w-4 h-4" :stroke-width="2" />
                        {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Apply modal -->
        <AppModal
            :show="showApply"
            :title="t('personal_finance.budget_presets.apply_title', { name: applyTarget?.name ?? '' })"
            :icon="Play"
            :closeable="false"
            max-width="md"
            v-on:close="showApply = false"
        >
            <div v-if="applyTarget" class="space-y-4">
                <p class="text-sm text-muted">{{ t("personal_finance.budget_presets.apply_help") }}</p>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-muted mb-1">{{ `${t('personal_finance.budget_presets.fields.month')} *` }}</label>
                    <input
                        v-model="applyMonth"
                        type="month"
                        class="w-full bg-surface-2 border border-line rounded-md px-3 py-2 text-sm text-primary"
                    />
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-muted mb-2">{{ t("personal_finance.budget_presets.fields.mode") }}</label>
                    <div class="flex items-center gap-2">
                        <AppTab
                            v-for="m in applyModes"
                            :key="m"
                            variant="pill"
                            size="sm"
                            :active="applyMode === m"
                            v-on:click="applyMode = m"
                        >
                            {{ t(`personal_finance.budget_presets.modes.${m}`) }}
                        </AppTab>
                    </div>
                    <p class="text-xs text-muted mt-2">{{ t(`personal_finance.budget_presets.mode_descriptions.${applyMode}`) }}</p>
                </div>
            </div>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" v-on:click="showApply = false">
                        <X class="w-4 h-4" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" :loading="applyLoading" v-on:click="submitApply">
                        <Play class="w-4 h-4" :stroke-width="2" />
                        {{ t("personal_finance.budget_presets.apply") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Delete confirm -->
        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">
                {{ t("personal_finance.budget_presets.delete_confirm", { name: pendingDelete?.name ?? '' }) }}
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

        <slot name="extra-cards" :presets="presets" />
    </div>
</template>
