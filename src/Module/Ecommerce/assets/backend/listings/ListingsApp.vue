<script setup>
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useListingsProducts } from "@ecommerce/backend/listings/composables/useListingsProducts.js";
import { useListingsCategories } from "@ecommerce/backend/listings/composables/useListingsCategories.js";
import { useListingsTags } from "@ecommerce/backend/listings/composables/useListingsTags.js";
import { useListingsCreate } from "@ecommerce/backend/listings/composables/useListingsCreate.js";
import { useListingsEdit } from "@ecommerce/backend/listings/composables/useListingsEdit.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppSelect from "@/shared/components/form/select/AppSelect.vue";
import AppTextarea from "@/shared/components/form/input/AppTextarea.vue";
import AppToggle from "@/shared/components/form/toggle/AppToggle.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppImagePickerField from "@/shared/components/form/file/AppImagePickerField.vue";
import AppImage from "@/shared/components/display/AppImage.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import { Pencil, Trash2, Plus, Eye, Save, X, ShoppingBag } from "lucide-vue-next";
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
    categoriesPath: { type: String, required: true },
    tagsPath: { type: String, required: true },
});

const { items, loading, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    { initialSearch: props.search, initialData: props.listings },
);

const { availableProducts, loadProducts } = useListingsProducts(props.productsPath);
const { flatCategories } = useListingsCategories(props.categoriesPath);
const { flatTags } = useListingsTags(props.tagsPath);
const { showCreate, newListing, newListingImage, createErrors, createLoading, openCreate, onProductChange, submitCreate } =
    useListingsCreate(props.createPath, reset, loadProducts);

const { showEdit, editingListing, editForm, editFormImage, editErrors, editLoading, openEdit, submitEdit } =
    useListingsEdit(props.updatePath, reset);

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath, () => { reset(); loadProducts(); }, "backend.ecommerce.listings.deleted",
);

</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('backend.ecommerce.listings.search_placeholder')"
                v-on:search="onSearch"
            />
            <template #actions>
                <AppButton
                    v-if="can('ecommerce.listings.create')"
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    v-on:click="openCreate"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t('backend.ecommerce.listings.add') }}
                </AppButton>
            </template>
        </AppListToolbar>

        <div class="relative space-y-4">
            <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.listings.title') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.listings.slug') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('backend.ecommerce.listings.categories') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.ecommerce.listings.price') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.listings.visibility') }}</th>
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
                            <td class="px-6 py-3 hidden lg:table-cell">
                                <div v-if="listing.categories?.length" class="flex flex-wrap gap-1">
                                    <AppBadge v-for="category in listing.categories" :key="category.id" color="sky">
                                        {{ category.name }}
                                    </AppBadge>
                                </div>
                                <div v-if="listing.tags?.length" class="flex flex-wrap gap-1 mt-1">
                                    <AppBadge v-for="tag in listing.tags" :key="tag.id" :color="tag.color">
                                        {{ tag.name }}
                                    </AppBadge>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ formatProductPrice(listing.product) }}</td>
                            <td class="px-6 py-3">
                                <AppBadge :color="listing.isVisibleOnShop ? 'emerald' : 'slate'">
                                    {{ t(listing.isVisibleOnShop ? 'backend.ecommerce.listings.visible' : 'backend.ecommerce.listings.hidden') }}
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
                            <td :colspan="6" class="px-6 py-8 text-center text-sm text-muted">{{ t('backend.ecommerce.listings.empty') }}</td>
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
                            {{ t(listing.isVisibleOnShop ? 'backend.ecommerce.listings.visible' : 'backend.ecommerce.listings.hidden') }}
                        </AppBadge>
                    </div>
                    <div v-if="listing.categories?.length" class="flex flex-wrap gap-1">
                        <AppBadge v-for="category in listing.categories" :key="category.id" color="sky">
                            {{ category.name }}
                        </AppBadge>
                    </div>
                    <div v-if="listing.tags?.length" class="flex flex-wrap gap-1">
                        <AppBadge v-for="tag in listing.tags" :key="tag.id" :color="tag.color">
                            {{ tag.name }}
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
                <p v-if="!items?.length" class="py-8 text-center text-sm text-muted">{{ t('backend.ecommerce.listings.empty') }}</p>
            </div>

            <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />
            <AppLoader :active="loading" />
        </div>

        <AppModal
            :show="showCreate"
            :title="t('backend.ecommerce.listings.create')"
            :icon="ShoppingBag"
            :closeable="false"
            v-on:close="showCreate = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppSelect
                    v-model="newListing.productId"
                    :label="t('backend.ecommerce.listings.product')"
                    :error="createErrors.productId"
                    required
                    v-on:update:model-value="(v) => onProductChange(availableProducts.find(p => p.id == v), newListing)"
                >
                    <option value="" disabled>{{ t('backend.ecommerce.listings.select_product') }}</option>
                    <option v-for="product in availableProducts" :key="product.id" :value="product.id">{{ product.name }} ({{ product.reference }})</option>
                </AppSelect>
                <AppInput
                    v-model="newListing.slug"
                    :label="t('backend.ecommerce.listings.slug')"
                    :placeholder="t('backend.ecommerce.listings.slug_placeholder')"
                    :error="createErrors.slug"
                    required
                />
                <AppInput
                    v-model="newListing.marketingTitle"
                    :label="t('backend.ecommerce.listings.marketing_title')"
                    :placeholder="t('backend.ecommerce.listings.marketing_title_placeholder')"
                />
                <AppTextarea v-model="newListing.marketingDescription" :rows="4" :placeholder="t('backend.ecommerce.listings.marketing_description_placeholder')" />
                <AppImagePickerField
                    v-model="newListingImage"
                    :label="t('backend.ecommerce.listings.featured_image')"
                    :hint="t('backend.ecommerce.listings.featured_image_override_hint')"
                />
                <AppMultiselect
                    v-model="newListing.categoryIds"
                    :options="flatCategories"
                    :label="t('backend.ecommerce.listings.categories')"
                    :placeholder="t('backend.ecommerce.listings.categories_placeholder')"
                    multiple
                    track-by="id"
                    option-label="label"
                />
                <AppMultiselect
                    v-model="newListing.tagIds"
                    :options="flatTags"
                    :label="t('backend.ecommerce.listings.tags')"
                    :placeholder="t('backend.ecommerce.listings.tags_placeholder')"
                    multiple
                    track-by="id"
                    option-label="label"
                />
                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <span class="text-sm text-secondary">{{ t('backend.ecommerce.listings.visible_on_shop') }}</span>
                    <AppToggle v-model="newListing.isVisibleOnShop" />
                </div>
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
            :title="t('backend.ecommerce.listings.edit', { name: editingListing?.displayTitle ?? '' })"
            :icon="Pencil"
            :closeable="false"
            v-on:close="showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.slug"
                    :label="t('backend.ecommerce.listings.slug')"
                    :error="editErrors.slug"
                    required
                />
                <AppInput
                    v-model="editForm.marketingTitle"
                    :label="t('backend.ecommerce.listings.marketing_title')"
                />
                <AppTextarea v-model="editForm.marketingDescription" :rows="4" />
                <AppImagePickerField
                    v-model="editFormImage"
                    :label="t('backend.ecommerce.listings.featured_image')"
                    :hint="t('backend.ecommerce.listings.featured_image_override_hint')"
                />
                <AppMultiselect
                    v-model="editForm.categoryIds"
                    :options="flatCategories"
                    :label="t('backend.ecommerce.listings.categories')"
                    :placeholder="t('backend.ecommerce.listings.categories_placeholder')"
                    multiple
                    track-by="id"
                    option-label="label"
                />
                <AppMultiselect
                    v-model="editForm.tagIds"
                    :options="flatTags"
                    :label="t('backend.ecommerce.listings.tags')"
                    :placeholder="t('backend.ecommerce.listings.tags_placeholder')"
                    multiple
                    track-by="id"
                    option-label="label"
                />
                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <span class="text-sm text-secondary">{{ t('backend.ecommerce.listings.visible_on_shop') }}</span>
                    <AppToggle v-model="editForm.isVisibleOnShop" />
                </div>
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
            <p class="text-sm text-primary">{{ t('backend.ecommerce.listings.delete_confirm', { name: pendingDelete?.displayTitle ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.ecommerce.listings.delete_warning') }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
