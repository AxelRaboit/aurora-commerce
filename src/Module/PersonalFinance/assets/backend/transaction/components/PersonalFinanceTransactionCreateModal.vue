<script setup>
import { computed, defineExpose, defineEmits } from "vue";
import { useI18n } from "vue-i18n";
import { Save, X, Receipt } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppAmountInput from "@/shared/components/form/input/AppAmountInput.vue";
import AppDatePicker from "@/shared/components/form/picker/AppDatePicker.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { useTransactionsCreate } from "../composables/useTransactionsCreate.js";

const props = defineProps({
    /** Wallets the user can post transactions on. */
    wallets: { type: Array, required: true },
    /** { [walletId]: Category[] } — used to compute the category dropdown for the targeted wallet. */
    categoriesByWallet: { type: Object, required: true },
    /** Available transaction types (income / expense). */
    types: { type: Array, required: true },
    /** Path containing `__walletId__` placeholder. */
    createPath: { type: String, required: true },
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["created"]);

const { t } = useI18n();

const {
    showCreate,
    createForm,
    createErrors,
    createLoading,
    openCreate,
    submitCreate,
    targetWalletId,
} = useTransactionsCreate(
    props.createPath,
    (transaction, walletId) => emit("created", { transaction, walletId }),
    { extraFields: props.extraFields },
);

const typeOptions = computed(() =>
    props.types.map((ty) => ({ value: ty, label: t(`personal_finance.transactions.types.${ty}`) })),
);

const categoryOptions = computed(() => {
    const wid = targetWalletId.value;
    if (!wid) return [{ value: null, label: t("personal_finance.transactions.uncategorized") }];
    const list = props.categoriesByWallet[String(wid)] ?? [];
    return [
        { value: null, label: t("personal_finance.transactions.uncategorized") },
        ...list.map((c) => ({ value: c.id, label: c.name })),
    ];
});

defineExpose({ open: openCreate });
</script>

<template>
    <AppModal
        :show="showCreate"
        :title="t('personal_finance.transactions.create_form_title')"
        :icon="Receipt"
        :closeable="false"
        v-on:close="showCreate = false"
    >
        <form class="space-y-4" v-on:submit.prevent="submitCreate">
            <AppMultiselect
                v-model="createForm.type"
                :label="t('personal_finance.transactions.fields.type')"
                :placeholder="t('personal_finance.transactions.placeholders.type')"
                :options="typeOptions"
                :allow-empty="false"
                required
            />
            <AppAmountInput
                v-model="createForm.amount"
                :label="t('personal_finance.transactions.fields.amount')"
                :placeholder="t('personal_finance.transactions.placeholders.amount')"
                :error="createErrors.amount"
                required
            />
            <AppDatePicker
                v-model="createForm.date"
                :label="t('personal_finance.transactions.fields.date')"
                :placeholder="t('personal_finance.transactions.placeholders.date')"
                :error="createErrors.date"
                required
            />
            <AppMultiselect
                v-model="createForm.categoryId"
                :label="t('personal_finance.transactions.fields.category')"
                :placeholder="t('personal_finance.transactions.placeholders.category')"
                :options="categoryOptions"
                :allow-empty="true"
            />
            <AppInput
                v-model="createForm.description"
                :label="t('personal_finance.transactions.fields.description')"
                :placeholder="t('personal_finance.transactions.placeholders.description')"
                :error="createErrors.description"
            />
            <slot name="extra-form-fields" :form="createForm" :errors="createErrors" />
        </form>
        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false">
                    <X class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("shared.common.cancel") }}
                </AppButton>
                <AppButton variant="primary" size="md" type="submit" :loading="createLoading" v-on:click="submitCreate">
                    <Save class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("shared.common.save") }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>
</template>
