<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useApiRequest } from "@/shared/composables/useApiRequest.js";
import { useForm } from "@/shared/composables/useForm.js";
import { useDateFormat } from "@/shared/composables/useDateFormat.js";
import { useLoadMore } from "@/shared/composables/useLoadMore.js";
import AppButton from "@/shared/components/AppButton.vue";
import AppIconButton from "@/shared/components/AppIconButton.vue";
import AppInput from "@/shared/components/AppInput.vue";
import AppSelect from "@/shared/components/AppSelect.vue";
import AppTextarea from "@/shared/components/AppTextarea.vue";
import AppBadge from "@/shared/components/AppBadge.vue";
import AppModal from "@/shared/components/AppModal.vue";
import AppModalFooter from "@/shared/components/AppModalFooter.vue";
import AppLoadMore from "@/shared/components/AppLoadMore.vue";
import { Pencil, Trash2, Package } from "lucide-vue-next";
import { required } from "@/shared/utils/validators.js";
import { toast } from "vue-sonner";

const { t } = useI18n();
const { formatDateTime } = useDateFormat();

const props = defineProps({
    product: { type: Object, required: true },
    activity: { type: Object, default: () => ({ items: [], total: 0, page: 1, totalPages: 1 }) },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    backPath: { type: String, required: true },
    activityPath: { type: String, required: true },
});

const STATUS_OPTIONS = [
    { value: "draft", label: () => t("admin.erp.products.status.draft") },
    { value: "active", label: () => t("admin.erp.products.status.active") },
    { value: "archived", label: () => t("admin.erp.products.status.archived") },
];

const STATUS_TONE = { draft: "amber", active: "emerald", archived: "slate" };

const TYPE_OPTIONS = [
    { value: "physical", label: () => t("admin.erp.products.types.physical") },
    { value: "digital", label: () => t("admin.erp.products.types.digital") },
    { value: "service", label: () => t("admin.erp.products.types.service") },
];

const TYPE_TONE = { physical: "slate", digital: "indigo", service: "violet" };

const CURRENCY_OPTIONS = [
    { value: "EUR", symbol: "€" },
    { value: "USD", symbol: "$" },
    { value: "GBP", symbol: "£" },
    { value: "CHF", symbol: "CHF" },
    { value: "JPY", symbol: "¥" },
    { value: "CAD", symbol: "$" },
    { value: "AUD", symbol: "$" },
    { value: "SEK", symbol: "kr" },
];

const CURRENCY_BY_CODE = Object.fromEntries(CURRENCY_OPTIONS.map((c) => [c.value, c]));
const symbolFor = (code) => CURRENCY_BY_CODE[code]?.symbol ?? code;

function formatPrice(product) {
    if (product.price === null || product.price === undefined) return "—";
    try {
        return new Intl.NumberFormat(undefined, { style: "currency", currency: product.currency || "EUR" }).format(product.price);
    } catch {
        return `${product.price.toFixed(product.currencyDecimals ?? 2)} ${product.currency || ""}`;
    }
}

const product = ref({ ...props.product });

// --- Edit ---
const showEdit = ref(false);
const editForm = ref({
    name: product.value.name,
    sku: product.value.sku ?? "",
    description: product.value.description ?? "",
    price: product.value.price ?? "",
    currency: product.value.currency ?? "EUR",
    status: product.value.status ?? "draft",
    type: product.value.type ?? "physical",
});
const { errors: editErrors, validate: validateEdit, setErrors: setEditErrors } = useForm();
const { loading: editLoading, request: editRequest } = useApiRequest();

async function submitEdit() {
    if (!validateEdit({
        name: () => required(t("admin.erp.products.errors.name_required"))(editForm.value.name),
    })) return;

    const data = await editRequest(props.updatePath, {
        name: editForm.value.name,
        sku: editForm.value.sku,
        description: editForm.value.description,
        price: editForm.value.price === "" ? null : editForm.value.price,
        currency: editForm.value.currency,
        status: editForm.value.status,
        type: editForm.value.type,
    });
    if (!data) return;
    if (data.success) {
        product.value = { ...product.value, ...(data.product ?? editForm.value) };
        showEdit.value = false;
        toast.success(t("shared.common.saved"));
    } else {
        setEditErrors(data.errors ?? {});
    }
}

// --- Activity (load more) ---
const { items: activityItems, hasMore: hasMoreActivity, loading: loadingActivity, loadMore: loadMoreActivity } =
    useLoadMore(props.activityPath, props.activity);

// --- Delete ---
const showDelete = ref(false);
const { loading: deleteLoading, request: deleteRequest } = useApiRequest();

async function doDelete() {
    const data = await deleteRequest(props.deletePath, {});
    if (data?.success) window.location.href = props.backPath;
}

const actionLabel = (action) => {
    const map = {
        "product.created": t("admin.erp.activity.created"),
        "product.updated": t("admin.erp.activity.updated"),
        "product.deleted": t("admin.erp.activity.deleted"),
    };
    return map[action] ?? action;
};
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Product card -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-surface border border-line rounded-lg p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-6">
                    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                        <div v-if="product.image" class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg overflow-hidden shrink-0">
                            <img :src="product.image.url" :alt="product.image.alt ?? product.name" class="w-full h-full object-cover">
                        </div>
                        <div v-else class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg bg-indigo-600/20 text-indigo-400 flex items-center justify-center shrink-0">
                            <Package class="w-5 h-5 sm:w-6 sm:h-6" :stroke-width="2" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg sm:text-xl font-bold text-primary break-words">{{ product.name }}</h2>
                            <p class="text-xs font-mono text-muted mt-0.5 break-all">{{ product.sku }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between sm:justify-end gap-2 sm:shrink-0">
                        <AppBadge :color="STATUS_TONE[product.status] ?? 'slate'">
                            {{ t(`admin.erp.products.status.${product.status}`) }}
                        </AppBadge>
                        <div class="flex items-center gap-1">
                            <AppIconButton color="indigo" :title="t('shared.common.edit')" v-on:click="showEdit = true"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="showDelete = true"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        </div>
                    </div>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.erp.products.price') }}</dt>
                        <dd class="text-primary font-medium">{{ formatPrice(product) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.erp.products.currency') }}</dt>
                        <dd class="text-primary">{{ product.currency }} <span class="text-muted">({{ product.currencySymbol }})</span></dd>
                    </div>
                    <div v-if="product.description" class="sm:col-span-2">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.erp.products.description') }}</dt>
                        <dd class="text-secondary text-sm whitespace-pre-wrap break-words">{{ product.description }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Activity timeline -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-primary uppercase tracking-wide">{{ t('admin.erp.activity.title') }}</h3>

            <div v-if="!activityItems.length" class="text-sm text-muted">{{ t('admin.erp.activity.empty') }}</div>

            <template v-else>
                <ol class="relative border-l border-line ml-3 space-y-6">
                    <li v-for="event in activityItems" :key="event.id" class="ml-4">
                        <div class="absolute w-2.5 h-2.5 bg-indigo-600 rounded-full -left-1.5 border-2 border-bg" />
                        <p class="text-sm font-medium text-primary">{{ actionLabel(event.action) }}</p>
                        <p class="text-xs text-secondary">
                            <span v-if="event.userName">{{ event.userName }}</span>
                            <span v-if="event.userName && event.userEmail" class="text-muted"> · </span>
                            <span v-if="event.userEmail" class="text-muted">{{ event.userEmail }}</span>
                        </p>
                        <time class="text-xs text-muted">{{ formatDateTime(event.createdAt) }}</time>
                    </li>
                </ol>

                <AppLoadMore
                    :has-more="hasMoreActivity"
                    :loading="loadingActivity"
                    v-on:load="loadMoreActivity"
                />
            </template>
        </div>

        <!-- Edit modal -->
        <AppModal :show="showEdit" v-on:close="showEdit = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.erp.products.edit', { name: product.name }) }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('admin.erp.products.name')"
                    :placeholder="t('admin.erp.products.namePlaceholder')"
                    :error="editErrors.name"
                    required
                />
                <div class="grid grid-cols-2 gap-3">
                    <AppInput
                        v-model="editForm.sku"
                        :label="t('admin.erp.products.sku')"
                        :placeholder="t('admin.erp.products.skuAutoPlaceholder')"
                        :error="editErrors.sku"
                    />
                    <AppSelect v-model="editForm.status" :label="t('admin.erp.products.statusLabel')">
                        <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label() }}</option>
                    </AppSelect>
                </div>
                <AppSelect v-model="editForm.type" :label="t('admin.erp.products.typeLabel')">
                    <option v-for="opt in TYPE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label() }}</option>
                </AppSelect>
                <div class="grid grid-cols-[1fr_8rem] gap-3">
                    <AppInput
                        v-model="editForm.price"
                        type="number"
                        min="0"
                        step="0.01"
                        :label="`${t('admin.erp.products.price')} (${symbolFor(editForm.currency)})`"
                        :placeholder="t('admin.erp.products.pricePlaceholder')"
                        :error="editErrors.price ?? editErrors.priceCents"
                    />
                    <AppSelect v-model="editForm.currency" :label="t('admin.erp.products.currency')">
                        <option v-for="opt in CURRENCY_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.value }} — {{ opt.symbol }}</option>
                    </AppSelect>
                </div>
                <AppTextarea v-model="editForm.description" :rows="3" :placeholder="t('admin.erp.products.descriptionPlaceholder')" />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading">{{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <!-- Delete confirm -->
        <AppModal :show="showDelete" max-width="sm" v-on:close="showDelete = false">
            <p class="text-sm text-primary">{{ t('admin.erp.products.deleteConfirm', { name: product.name }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.erp.products.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="showDelete = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
