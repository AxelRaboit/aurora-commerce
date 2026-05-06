<script setup>
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useListingsProducts } from "@ecommerce/admin/listings/composables/useListingsProducts.js";
import { useListingsCreate } from "@ecommerce/admin/listings/composables/useListingsCreate.js";
import { useListingsEdit } from "@ecommerce/admin/listings/composables/useListingsEdit.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppToggle from "@/shared/components/form/AppToggle.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppImagePickerField from "@/shared/components/form/AppImagePickerField.vue";
import AppImage from "@/shared/components/display/AppImage.vue";
import { Pencil, Trash2, Plus, Eye, Save, } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { required } from "@/shared/utils/validation/validators.js";
import { formatProductPrice } from "@/shared/utils/format/formatPrice.js";
import { slugifyIfEmpty } from "@/shared/utils/format/slugify.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    listings: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    showPath: { type: String, default: "" },
    listPath: { type: String, required: true },
    productsPath: { type: String, required: true },
});

const { items, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    { initialSearch: props.search, initialData: props.listings },
);

const { availableProducts, loadProducts } = useListingsProducts(props.productsPath);
const { showCreate, newListing, newListingImage, createErrors, createLoading, openCreate, onProductChange, submitCreate } =
    useListingsCreate(props.createPath, reset, loadProducts);

const { showEdit, editingListing, editForm, editFormImage, editErrors, editLoading, openEdit, submitEdit } =
    useListingsEdit(props.updatePath, reset);

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath, () => { reset(); loadProducts(); }, "admin.ecommerce.listings.deleted",
);

</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('admin.ecommerce.listings.searchPlaceholder')"
                v-on:search="onSearch"
            />
            <AppButton
                v-if="can('ecommerce.listings.create')"
                variant="primary"
                size="md"
                class="w-full sm:w-auto"
                v-on:click="openCreate"
            >
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.ecommerce.listings.add') }}
            </AppButton>
        </div>

        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.ecommerce.listings.title') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.ecommerce.listings.slug') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('admin.ecommerce.listings.price') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.ecommerce.listings.visibility') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="listing in items" :key="listing.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded bg-surface-2 overflow-hidden shrink-0 flex items-center justify-center">
                                    <AppImage v-if="listing.displayImage" :src="listing.displayImage.url" :alt="listing.displayImage.alt ?? listing.displayTitle" object-fit="cover" />
                                    <span v-else class="text-muted text-xs">—</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-primary truncate">{{ listing.displayTitle }}</div>
                                    <div class="text-xs font-mono text-muted">{{ listing.product.reference }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3 font-mono text-xs text-secondary">/{{ listing.slug }}</td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ formatProductPrice(listing.product) }}</td>
                        <td class="px-6 py-3">
                            <AppBadge :color="listing.isVisibleOnShop ? 'emerald' : 'slate'">
                                {{ t(listing.isVisibleOnShop ? 'admin.ecommerce.listings.visible' : 'admin.ecommerce.listings.hidden') }}
                            </AppBadge>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="showPath" color="sky" :href="buildPath(showPath, { id: listing.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('ecommerce.listings.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(listing)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('ecommerce.listings.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(listing)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items?.length">
                        <td :colspan="5" class="px-6 py-8 text-center text-sm text-muted">{{ t('admin.ecommerce.listings.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="sm:hidden space-y-3">
            <div v-for="listing in items" :key="listing.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-primary truncate">{{ listing.displayTitle }}</p>
                        <p class="text-xs font-mono text-muted mt-0.5 truncate">/{{ listing.slug }}</p>
                    </div>
                    <AppBadge :color="listing.isVisibleOnShop ? 'emerald' : 'slate'">
                        {{ t(listing.isVisibleOnShop ? 'admin.ecommerce.listings.visible' : 'admin.ecommerce.listings.hidden') }}
                    </AppBadge>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <p class="text-sm text-secondary">{{ formatProductPrice(listing.product) }}</p>
                    <div class="flex items-center gap-0.5">
                        <AppIconButton v-if="showPath" color="sky" :href="buildPath(showPath, { id: listing.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton v-if="can('ecommerce.listings.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(listing)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton v-if="can('ecommerce.listings.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(listing)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>
            </div>
            <p v-if="!items?.length" class="py-8 text-center text-sm text-muted">{{ t('admin.ecommerce.listings.empty') }}</p>
        </div>

        <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />

        <AppModal :show="showCreate" v-on:close="showCreate = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.ecommerce.listings.create') }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppSelect
                    v-model="newListing.productId"
                    :label="t('admin.ecommerce.listings.product')"
                    :error="createErrors.productId"
                    required
                    v-on:update:model-value="(v) => onProductChange(availableProducts.find(p => p.id == v), newListing)"
                >
                    <option value="" disabled>{{ t('admin.ecommerce.listings.selectProduct') }}</option>
                    <option v-for="product in availableProducts" :key="product.id" :value="product.id">{{ product.name }} ({{ product.reference }})</option>
                </AppSelect>
                <AppInput
                    v-model="newListing.slug"
                    :label="t('admin.ecommerce.listings.slug')"
                    :placeholder="t('admin.ecommerce.listings.slugPlaceholder')"
                    :error="createErrors.slug"
                    required
                />
                <AppInput
                    v-model="newListing.marketingTitle"
                    :label="t('admin.ecommerce.listings.marketingTitle')"
                    :placeholder="t('admin.ecommerce.listings.marketingTitlePlaceholder')"
                />
                <AppTextarea v-model="newListing.marketingDescription" :rows="4" :placeholder="t('admin.ecommerce.listings.marketingDescriptionPlaceholder')" />
                <AppImagePickerField
                    v-model="newListingImage"
                    :label="t('admin.ecommerce.listings.featuredImage')"
                    :hint="t('admin.ecommerce.listings.featuredImageOverrideHint')"
                />
                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <span class="text-sm text-secondary">{{ t('admin.ecommerce.listings.visibleOnShop') }}</span>
                    <AppToggle v-model="newListing.isVisibleOnShop" />
                </div>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="createLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="showEdit" v-on:close="showEdit = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.ecommerce.listings.edit', { name: editingListing?.displayTitle ?? '' }) }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.slug"
                    :label="t('admin.ecommerce.listings.slug')"
                    :error="editErrors.slug"
                    required
                />
                <AppInput
                    v-model="editForm.marketingTitle"
                    :label="t('admin.ecommerce.listings.marketingTitle')"
                />
                <AppTextarea v-model="editForm.marketingDescription" :rows="4" />
                <AppImagePickerField
                    v-model="editFormImage"
                    :label="t('admin.ecommerce.listings.featuredImage')"
                    :hint="t('admin.ecommerce.listings.featuredImageOverrideHint')"
                />
                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <span class="text-sm text-secondary">{{ t('admin.ecommerce.listings.visibleOnShop') }}</span>
                    <AppToggle v-model="editForm.isVisibleOnShop" />
                </div>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="!!pendingDelete" max-width="sm" v-on:close="pendingDelete = null">
            <p class="text-sm text-primary">{{ t('admin.ecommerce.listings.deleteConfirm', { name: pendingDelete?.displayTitle ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.ecommerce.listings.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
