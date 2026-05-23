<script setup>
import { computed, ref, defineExpose, defineEmits } from "vue";
import { useI18n } from "vue-i18n";
import { Save, X, Pencil, Trash2, FileDown, Upload } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppAmountInput from "@/shared/components/form/input/AppAmountInput.vue";
import AppDatePicker from "@/shared/components/form/picker/AppDatePicker.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { useTransactionsEdit } from "../composables/useTransactionsEdit.js";
import { useTransactionAttachment } from "../composables/useTransactionAttachment.js";

const props = defineProps({
    /** { [walletId]: Category[] } — used to compute the category dropdown for the edited transaction's wallet. */
    categoriesByWallet: { type: Object, required: true },
    /** Available transaction types (income / expense). */
    types: { type: Array, required: true },
    /** Update path with `__id__` placeholder. */
    updatePath: { type: String, required: true },
    /** Attachment upload path with `__id__` placeholder. */
    uploadAttachmentPath: { type: String, required: true },
    /** Attachment delete path with `__id__` placeholder. */
    deleteAttachmentPath: { type: String, required: true },
    /** Attachment serve path with `__id__` placeholder. */
    serveAttachmentPath: { type: String, required: true },
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["updated", "attachment-changed"]);

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const {
    showEdit,
    editingTransaction,
    editForm,
    editErrors,
    editLoading,
    openEdit,
    submitEdit,
} = useTransactionsEdit(
    props.updatePath,
    (transaction) => emit("updated", transaction),
    { extraFields: props.extraFields },
);

const { loading: attachmentLoading, upload: uploadAttachment, remove: removeAttachment, serveUrl: attachmentServeUrl } =
    useTransactionAttachment(
        props.uploadAttachmentPath,
        props.deleteAttachmentPath,
        props.serveAttachmentPath,
        (updatedTransaction) => {
            if (updatedTransaction && editingTransaction.value && updatedTransaction.id === editingTransaction.value.id) {
                editingTransaction.value = updatedTransaction;
            }
            emit("attachment-changed", updatedTransaction);
        },
    );

const attachmentInput = ref(null);

function onAttachmentPick(event) {
    const file = event.target.files?.[0];
    if (file && editingTransaction.value?.id) {
        uploadAttachment(editingTransaction.value.id, file);
    }
    event.target.value = "";
}

function onAttachmentRemove() {
    if (editingTransaction.value?.id) {
        removeAttachment(editingTransaction.value.id);
    }
}

const typeOptions = computed(() =>
    props.types.map((ty) => ({ value: ty, label: t(`personal_finance.transactions.types.${ty}`) })),
);

const categoryOptions = computed(() => {
    const wid = editingTransaction.value?.walletId;
    if (!wid) return [{ value: null, label: t("personal_finance.transactions.uncategorized") }];
    const list = props.categoriesByWallet[String(wid)] ?? [];
    return [
        { value: null, label: t("personal_finance.transactions.uncategorized") },
        ...list.map((c) => ({ value: c.id, label: c.name })),
    ];
});

function describeTx(tx) {
    if (!tx) return "";
    const sign = tx.type === "income" ? "+" : "-";
    return `${formatDateShort(tx.date)} · ${sign}${tx.amount}`;
}

defineExpose({ open: openEdit });
</script>

<template>
    <AppModal
        :show="showEdit"
        :title="t('personal_finance.transactions.edit', { name: describeTx(editingTransaction) })"
        :icon="Pencil"
        :closeable="false"
        v-on:close="showEdit = false"
    >
        <form class="space-y-4" v-on:submit.prevent="submitEdit">
            <AppMultiselect
                v-model="editForm.type"
                :label="t('personal_finance.transactions.fields.type')"
                :placeholder="t('personal_finance.transactions.placeholders.type')"
                :options="typeOptions"
                :allow-empty="false"
                required
            />
            <AppAmountInput
                v-model="editForm.amount"
                :label="t('personal_finance.transactions.fields.amount')"
                :placeholder="t('personal_finance.transactions.placeholders.amount')"
                :error="editErrors.amount"
                required
            />
            <AppDatePicker
                v-model="editForm.date"
                :label="t('personal_finance.transactions.fields.date')"
                :placeholder="t('personal_finance.transactions.placeholders.date')"
                :error="editErrors.date"
                required
            />
            <AppMultiselect
                v-model="editForm.categoryId"
                :label="t('personal_finance.transactions.fields.category')"
                :placeholder="t('personal_finance.transactions.placeholders.category')"
                :options="categoryOptions"
                :allow-empty="true"
            />
            <AppInput
                v-model="editForm.description"
                :label="t('personal_finance.transactions.fields.description')"
                :placeholder="t('personal_finance.transactions.placeholders.description')"
                :error="editErrors.description"
            />
            <slot name="extra-form-fields" :form="editForm" :errors="editErrors" :transaction="editingTransaction" />

            <div v-if="editingTransaction" class="border-t border-line pt-4 space-y-2">
                <label class="text-xs font-medium uppercase tracking-wider text-muted">
                    {{ t("personal_finance.transactions.attachment.label") }}
                </label>
                <div v-if="editingTransaction.hasAttachment" class="flex items-center justify-between gap-3 bg-surface-2/40 border border-line rounded-md px-3 py-2">
                    <a
                        :href="attachmentServeUrl(editingTransaction.id)"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-2 text-sm text-accent hover:underline truncate"
                    >
                        <FileDown class="w-4 h-4" :stroke-width="2" />
                        <span class="truncate">{{ editingTransaction.attachmentOriginalName ?? t('personal_finance.transactions.attachment.download') }}</span>
                    </a>
                    <AppIconButton
                        color="rose"
                        :title="t('personal_finance.transactions.attachment.remove')"
                        :disabled="attachmentLoading"
                        v-on:click="onAttachmentRemove"
                    >
                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                </div>
                <div v-else class="flex items-center gap-2">
                    <AppButton
                        variant="ghost"
                        size="sm"
                        type="button"
                        :loading="attachmentLoading"
                        v-on:click="attachmentInput?.click()"
                    >
                        <Upload class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("personal_finance.transactions.attachment.add") }}
                    </AppButton>
                    <span class="text-xs text-muted">{{ t("personal_finance.transactions.attachment.hint") }}</span>
                </div>
                <input
                    ref="attachmentInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp,application/pdf"
                    class="hidden"
                    v-on:change="onAttachmentPick"
                />
            </div>
        </form>
        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false">
                    <X class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("shared.common.cancel") }}
                </AppButton>
                <AppButton variant="primary" size="md" type="submit" :loading="editLoading" v-on:click="submitEdit">
                    <Save class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("shared.common.save") }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>
</template>
