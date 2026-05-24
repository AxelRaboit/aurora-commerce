<script setup>
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useProductsOptions } from "@erp/backend/products/composables/useProductsOptions.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useProductsCreate } from "@erp/backend/products/composables/useProductsCreate.js";
import { useProductsEdit } from "@erp/backend/products/composables/useProductsEdit.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppSelect from "@/shared/components/form/select/AppSelect.vue";
import AppTextarea from "@/shared/components/form/input/AppTextarea.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppImagePickerField from "@/shared/components/form/file/AppImagePickerField.vue";
import AppImage from "@/shared/components/display/AppImage.vue";
import AppThumbnail from "@/shared/components/display/AppThumbnail.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import { Pencil, Trash2, Plus, Eye, Save, X, Package } from "lucide-vue-next";
import { formatProductPrice } from "@/shared/utils/format/formatPrice.js";
import { CURRENCY_OPTIONS, symbolFor } from "@/shared/utils/format/currencies.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    products: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    showPath: { type: String, default: "" },
    listPath: { type: String, required: true },
});

const { items, loading, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    { initialSearch: props.search, initialData: props.products },
);

const { STATUS_OPTIONS, STATUS_TONE, TYPE_OPTIONS, TYPE_TONE } = useProductsOptions();

const { showCreate, newProduct, newProductImage, createErrors, createLoading, openCreate, submitCreate } = useProductsCreate(props.createPath, reset);
const { showEdit, editingProduct, editForm, editFormImage, editErrors, editLoading, openEdit, submitEdit } = useProductsEdit(props.updatePath, reset);
const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(props.deletePath, () => reset(), "backend.erp.products.deleted");
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('backend.erp.products.search_placeholder')"
                v-on:search="onSearch"
            />
            <template #actions>
                <AppButton
                    v-if="can('erp.products.create')"
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    v-on:click="openCreate"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t('backend.erp.products.add') }}
                </AppButton>
            </template>
        </AppListToolbar>

        <div class="relative space-y-4">
            <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.erp.products.name') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.erp.products.reference') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.erp.products.price') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('backend.erp.products.stock') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.erp.products.status_label') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-for="product in items" :key="product.id" class="group hover:bg-surface-2/40 transition-colors">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <AppThumbnail :src="product.image?.url" :alt="product.image?.alt ?? product.name">
                                        <span class="text-muted text-xs">—</span>
                                    </AppThumbnail>
                                    <span class="font-medium text-primary truncate">{{ product.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3 font-mono text-xs text-secondary">{{ product.reference }}</td>
                            <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ formatProductPrice(product) }}</td>
                            <td class="px-6 py-3 hidden lg:table-cell">
                                <span v-if="!product.stockTracked" class="text-xs text-muted">{{ t('backend.erp.products.stock_untracked') }}</span>
                                <AppBadge v-else-if="product.stockQuantity === 0" color="rose">{{ t('backend.erp.products.stock_out') }}</AppBadge>
                                <AppBadge v-else-if="product.isLowStock" color="amber">{{ product.stockQuantity }}</AppBadge>
                                <span v-else class="text-secondary tabular-nums">{{ product.stockQuantity }}</span>
                            </td>
                            <td class="px-6 py-3">
                                <AppBadge :color="STATUS_TONE[product.status] ?? 'slate'">{{ t(`backend.erp.products.status.${product.status}`) }}</AppBadge>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppIconButton v-if="showPath" color="sky" :href="buildPath(showPath, { id: product.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    <AppIconButton v-if="can('erp.products.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(product)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    <AppIconButton v-if="can('erp.products.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(product)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!items?.length">
                            <td :colspan="6" class="px-6 py-8 text-center text-sm text-muted">{{ t('backend.erp.products.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="sm:hidden space-y-3">
                <div v-for="product in items" :key="product.id" class="bg-surface border border-line rounded-lg p-4 space-y-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-primary truncate">{{ product.name }}</p>
                            <p class="text-xs font-mono text-muted mt-0.5">{{ product.reference }}</p>
                        </div>
                        <AppBadge :color="STATUS_TONE[product.status] ?? 'slate'">{{ t(`backend.erp.products.status.${product.status}`) }}</AppBadge>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-line">
                        <p class="text-sm text-secondary">{{ formatProductPrice(product) }}</p>
                        <div class="flex items-center gap-0.5">
                            <AppIconButton v-if="showPath" color="sky" :href="buildPath(showPath, { id: product.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            <AppIconButton v-if="can('erp.products.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(product)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            <AppIconButton v-if="can('erp.products.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(product)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        </div>
                    </div>
                </div>
                <p v-if="!items?.length" class="py-8 text-center text-sm text-muted">{{ t('backend.erp.products.empty') }}</p>
            </div>

            <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />
            <AppLoader :active="loading" />
        </div>

        <AppModal
            :show="showCreate"
            :title="t('backend.erp.products.create')"
            :icon="Package"
            :closeable="false"
            v-on:close="showCreate = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppInput
                    v-model="newProduct.name"
                    :label="t('backend.erp.products.name')"
                    :placeholder="t('backend.erp.products.name_placeholder')"
                    :error="createErrors.name"
                    required
                />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppInput
                        v-model="newProduct.reference"
                        :label="t('backend.erp.products.reference')"
                        :placeholder="t('backend.erp.products.reference_auto_placeholder')"
                        :error="createErrors.reference"
                    />
                    <AppSelect v-model="newProduct.status" :label="t('backend.erp.products.status_label')">
                        <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </AppSelect>
                </div>
                <AppSelect v-model="newProduct.type" :label="t('backend.erp.products.type_label')">
                    <option v-for="opt in TYPE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </AppSelect>
                <div class="grid grid-cols-[1fr_8rem] gap-3">
                    <AppInput
                        v-model="newProduct.price"
                        type="number"
                        min="0"
                        step="0.01"
                        :label="`${t('backend.erp.products.price')} (${symbolFor(newProduct.currency)})`"
                        :placeholder="t('backend.erp.products.price_placeholder')"
                        :error="createErrors.price ?? createErrors.priceCents"
                    />
                    <AppSelect v-model="newProduct.currency" :label="t('backend.erp.products.currency')">
                        <option v-for="opt in CURRENCY_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.value }} — {{ opt.symbol }}</option>
                    </AppSelect>
                </div>
                <AppTextarea v-model="newProduct.description" :rows="3" :placeholder="t('backend.erp.products.description_placeholder')" />
                <AppImagePickerField
                    v-model="newProductImage"
                    :label="t('backend.erp.products.image')"
                />
                <AppInput
                    v-model="newProduct.stockQuantity"
                    type="number"
                    min="0"
                    :label="t('backend.erp.products.stock')"
                    :placeholder="t('backend.erp.products.stock_placeholder')"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="createLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="showEdit"
            :title="t('backend.erp.products.edit', { name: editingProduct?.name ?? '' })"
            :icon="Pencil"
            :closeable="false"
            v-on:close="showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput v-model="editForm.name" :label="t('backend.erp.products.name')" :error="editErrors.name" required />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppInput v-model="editForm.reference" :label="t('backend.erp.products.reference')" :error="editErrors.reference" />
                    <AppSelect v-model="editForm.status" :label="t('backend.erp.products.status_label')">
                        <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </AppSelect>
                </div>
                <AppSelect v-model="editForm.type" :label="t('backend.erp.products.type_label')">
                    <option v-for="opt in TYPE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </AppSelect>
                <div class="grid grid-cols-[1fr_8rem] gap-3">
                    <AppInput
                        v-model="editForm.price"
                        type="number"
                        min="0"
                        step="0.01"
                        :label="`${t('backend.erp.products.price')} (${symbolFor(editForm.currency)})`"
                        :error="editErrors.price ?? editErrors.priceCents"
                    />
                    <AppSelect v-model="editForm.currency" :label="t('backend.erp.products.currency')">
                        <option v-for="opt in CURRENCY_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.value }} — {{ opt.symbol }}</option>
                    </AppSelect>
                </div>
                <AppTextarea v-model="editForm.description" :rows="3" />
                <AppImagePickerField
                    v-model="editFormImage"
                    :label="t('backend.erp.products.image')"
                />
                <AppInput
                    v-model="editForm.stockQuantity"
                    type="number"
                    min="0"
                    :label="t('backend.erp.products.stock')"
                    :placeholder="t('backend.erp.products.stock_placeholder')"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
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
            <p class="text-sm text-primary">{{ t('backend.erp.products.delete_confirm', { name: pendingDelete?.name ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.erp.products.delete_warning') }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
