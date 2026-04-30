<script setup>
import { ref, computed } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppImagePickerField from "@/shared/components/form/AppImagePickerField.vue";
import AppImage from "@/shared/components/display/AppImage.vue";
import { Pencil, Trash2, Plus, Eye, Save, } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { required } from "@/shared/utils/validation/validators.js";
import { formatProductPrice } from "@/shared/utils/format/formatPrice.js";
import { CURRENCY_OPTIONS, symbolFor, DEFAULT_CURRENCY } from "@/shared/utils/format/currencies.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

const { t } = useI18n();

const props = defineProps({
    products: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    showPath: { type: String, default: "" },
    listPath: { type: String, required: true },
});

const { items, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    { initialSearch: props.search, initialData: props.products },
);

const STATUS_OPTIONS = computed(() => [
    { value: "draft", label: t("admin.erp.products.status.draft") },
    { value: "active", label: t("admin.erp.products.status.active") },
    { value: "archived", label: t("admin.erp.products.status.archived") },
]);

const STATUS_TONE = { draft: "amber", active: "emerald", archived: "slate" };

const TYPE_OPTIONS = computed(() => [
    { value: "physical", label: t("admin.erp.products.types.physical") },
    { value: "digital", label: t("admin.erp.products.types.digital") },
    { value: "service", label: t("admin.erp.products.types.service") },
]);

const TYPE_TONE = { physical: "slate", digital: "accent", service: "violet" };


function emptyForm() {
    return { name: "", sku: "", description: "", price: "", currency: DEFAULT_CURRENCY, status: "draft", type: "physical", imageId: null, imageUrl: null, stockQuantity: "" };
}

function makeImageRef(form) {
    return computed({
        get: () => ({ id: form.value.imageId, url: form.value.imageUrl }),
        set: (v) => {
            form.value.imageId = v.id;
            form.value.imageUrl = v.url;
        },
    });
}


// --- Create ---
const showCreate = ref(false);
const newProduct = ref(emptyForm());
const newProductImage = makeImageRef(newProduct);
const { errors: createErrors, validate: validateCreate, clearErrors: clearCreate, setErrors: setCreateErrors } = useForm();
const { loading: createLoading, request: createRequest } = useApiRequest();

function openCreate() {
    newProduct.value = emptyForm();
    clearCreate();
    showCreate.value = true;
}

async function submitCreate() {
    if (!validateCreate({
        name: () => required(t("admin.erp.products.errors.name_required"))(newProduct.value.name),
    })) return;

    const data = await createRequest(props.createPath, buildPayload(newProduct.value));
    if (!data) return;
    if (data.success) { showCreate.value = false; toast.success(t("admin.erp.products.created")); reset(); }
    else setCreateErrors(translateServerErrors(t, data.errors));
}

function buildPayload(form) {
    return {
        name: form.name,
        sku: form.sku,
        description: form.description,
        price: form.price === "" ? null : form.price,
        currency: form.currency,
        status: form.status,
        type: form.type,
        imageId: form.imageId,
        stockQuantity: form.stockQuantity === "" ? null : Number(form.stockQuantity),
    };
}

// --- Edit ---
const showEdit = ref(false);
const editingProduct = ref(null);
const editForm = ref(emptyForm());
const editFormImage = makeImageRef(editForm);
const { errors: editErrors, validate: validateEdit, clearErrors: clearEdit, setErrors: setEditErrors } = useForm();
const { loading: editLoading, request: editRequest } = useApiRequest();

function openEdit(product) {
    editingProduct.value = product;
    editForm.value = {
        name: product.name,
        sku: product.sku,
        description: product.description ?? "",
        price: product.price ?? "",
        currency: product.currency ?? DEFAULT_CURRENCY,
        status: product.status ?? "draft",
        type: product.type ?? "physical",
        imageId: product.image?.id ?? null,
        imageUrl: product.image?.url ?? null,
        stockQuantity: product.stockQuantity ?? "",
    };
    clearEdit();
    showEdit.value = true;
}

async function submitEdit() {
    if (!validateEdit({
        name: () => required(t("admin.erp.products.errors.name_required"))(editForm.value.name),
    })) return;

    const url = buildPath(props.updatePath, { id: editingProduct.value.id });
    const data = await editRequest(url, buildPayload(editForm.value));
    if (!data) return;
    if (data.success) { showEdit.value = false; toast.success(t("admin.erp.products.updated")); reset(); }
    else setEditErrors(translateServerErrors(t, data.errors));
}

// --- Delete ---
const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath, () => reset(), 'admin.erp.products.deleted',
);
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('admin.erp.products.searchPlaceholder')"
                v-on:search="onSearch"
            />
            <AppButton variant="primary" size="md" class="w-full sm:w-auto" v-on:click="openCreate">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.erp.products.add') }}
            </AppButton>
        </div>

        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 border-b border-line">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.erp.products.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.erp.products.sku') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('admin.erp.products.price') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('admin.erp.products.stock') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.erp.products.statusLabel') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="product in items" :key="product.id" class="hover:bg-surface-2/50 transition-colors">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded bg-surface-2 overflow-hidden shrink-0 flex items-center justify-center">
                                    <AppImage v-if="product.image" :src="product.image.url" :alt="product.image.alt ?? product.name" object-fit="cover" />
                                    <span v-else class="text-muted text-xs">—</span>
                                </div>
                                <span class="font-medium text-primary truncate">{{ product.name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3 font-mono text-xs text-secondary">{{ product.sku }}</td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ formatProductPrice(product) }}</td>
                        <td class="px-6 py-3 hidden lg:table-cell">
                            <span v-if="!product.stockTracked" class="text-xs text-muted">{{ t('admin.erp.products.stockUntracked') }}</span>
                            <AppBadge v-else-if="product.stockQuantity === 0" color="rose">{{ t('admin.erp.products.stockOut') }}</AppBadge>
                            <AppBadge v-else-if="product.isLowStock" color="amber">{{ product.stockQuantity }}</AppBadge>
                            <span v-else class="text-secondary tabular-nums">{{ product.stockQuantity }}</span>
                        </td>
                        <td class="px-6 py-3">
                            <AppBadge :color="STATUS_TONE[product.status] ?? 'slate'">{{ t(`admin.erp.products.status.${product.status}`) }}</AppBadge>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="showPath" color="sky" :href="buildPath(showPath, { id: product.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(product)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(product)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items?.length">
                        <td :colspan="6" class="px-6 py-8 text-center text-sm text-muted">{{ t('admin.erp.products.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="sm:hidden space-y-3">
            <div v-for="product in items" :key="product.id" class="bg-surface border border-line rounded-lg p-4 space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-primary truncate">{{ product.name }}</p>
                        <p class="text-xs font-mono text-muted mt-0.5">{{ product.sku }}</p>
                    </div>
                    <AppBadge :color="STATUS_TONE[product.status] ?? 'slate'">{{ t(`admin.erp.products.status.${product.status}`) }}</AppBadge>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <p class="text-sm text-secondary">{{ formatProductPrice(product) }}</p>
                    <div class="flex items-center gap-0.5">
                        <AppIconButton v-if="showPath" color="sky" :href="buildPath(showPath, { id: product.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(product)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(product)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>
            </div>
            <p v-if="!items?.length" class="py-8 text-center text-sm text-muted">{{ t('admin.erp.products.empty') }}</p>
        </div>

        <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />

        <AppModal :show="showCreate" v-on:close="showCreate = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.erp.products.create') }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppInput
                    v-model="newProduct.name"
                    :label="t('admin.erp.products.name')"
                    :placeholder="t('admin.erp.products.namePlaceholder')"
                    :error="createErrors.name"
                    required
                />
                <div class="grid grid-cols-2 gap-3">
                    <AppInput
                        v-model="newProduct.sku"
                        :label="t('admin.erp.products.sku')"
                        :placeholder="t('admin.erp.products.skuAutoPlaceholder')"
                        :error="createErrors.sku"
                    />
                    <AppSelect v-model="newProduct.status" :label="t('admin.erp.products.statusLabel')">
                        <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </AppSelect>
                </div>
                <AppSelect v-model="newProduct.type" :label="t('admin.erp.products.typeLabel')">
                    <option v-for="opt in TYPE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </AppSelect>
                <div class="grid grid-cols-[1fr_8rem] gap-3">
                    <AppInput
                        v-model="newProduct.price"
                        type="number"
                        min="0"
                        step="0.01"
                        :label="`${t('admin.erp.products.price')} (${symbolFor(newProduct.currency)})`"
                        :placeholder="t('admin.erp.products.pricePlaceholder')"
                        :error="createErrors.price ?? createErrors.priceCents"
                    />
                    <AppSelect v-model="newProduct.currency" :label="t('admin.erp.products.currency')">
                        <option v-for="opt in CURRENCY_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.value }} — {{ opt.symbol }}</option>
                    </AppSelect>
                </div>
                <AppTextarea v-model="newProduct.description" :rows="3" :placeholder="t('admin.erp.products.descriptionPlaceholder')" />
                <AppImagePickerField
                    v-model="newProductImage"
                    :label="t('admin.erp.products.image')"
                />
                <AppInput
                    v-model="newProduct.stockQuantity"
                    type="number"
                    min="0"
                    :label="t('admin.erp.products.stock')"
                    :placeholder="t('admin.erp.products.stockPlaceholder')"
                />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="createLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="showEdit" v-on:close="showEdit = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.erp.products.edit', { name: editingProduct?.name ?? '' }) }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput v-model="editForm.name" :label="t('admin.erp.products.name')" :error="editErrors.name" required />
                <div class="grid grid-cols-2 gap-3">
                    <AppInput v-model="editForm.sku" :label="t('admin.erp.products.sku')" :error="editErrors.sku" />
                    <AppSelect v-model="editForm.status" :label="t('admin.erp.products.statusLabel')">
                        <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </AppSelect>
                </div>
                <AppSelect v-model="editForm.type" :label="t('admin.erp.products.typeLabel')">
                    <option v-for="opt in TYPE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </AppSelect>
                <div class="grid grid-cols-[1fr_8rem] gap-3">
                    <AppInput
                        v-model="editForm.price"
                        type="number"
                        min="0"
                        step="0.01"
                        :label="`${t('admin.erp.products.price')} (${symbolFor(editForm.currency)})`"
                        :error="editErrors.price ?? editErrors.priceCents"
                    />
                    <AppSelect v-model="editForm.currency" :label="t('admin.erp.products.currency')">
                        <option v-for="opt in CURRENCY_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.value }} — {{ opt.symbol }}</option>
                    </AppSelect>
                </div>
                <AppTextarea v-model="editForm.description" :rows="3" />
                <AppImagePickerField
                    v-model="editFormImage"
                    :label="t('admin.erp.products.image')"
                />
                <AppInput
                    v-model="editForm.stockQuantity"
                    type="number"
                    min="0"
                    :label="t('admin.erp.products.stock')"
                    :placeholder="t('admin.erp.products.stockPlaceholder')"
                />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="!!pendingDelete" max-width="sm" v-on:close="pendingDelete = null">
            <p class="text-sm text-primary">{{ t('admin.erp.products.deleteConfirm', { name: pendingDelete?.name ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.erp.products.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="confirmDelete(null)">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
