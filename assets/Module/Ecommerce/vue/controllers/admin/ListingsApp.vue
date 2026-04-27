<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/useListPage.js";
import { useApiRequest } from "@/shared/composables/useApiRequest.js";
import { useDelete } from "@/shared/composables/useDelete.js";
import { useForm } from "@/shared/composables/useForm.js";
import AppButton from "@/shared/components/AppButton.vue";
import AppIconButton from "@/shared/components/AppIconButton.vue";
import AppInput from "@/shared/components/AppInput.vue";
import AppSelect from "@/shared/components/AppSelect.vue";
import AppTextarea from "@/shared/components/AppTextarea.vue";
import AppToggle from "@/shared/components/AppToggle.vue";
import AppModal from "@/shared/components/AppModal.vue";
import AppModalFooter from "@/shared/components/AppModalFooter.vue";
import AppPagination from "@/shared/components/AppPagination.vue";
import AppBadge from "@/shared/components/AppBadge.vue";
import AppSearchInput from "@/shared/components/AppSearchInput.vue";
import AppImagePickerField from "@/shared/components/AppImagePickerField.vue";
import { Pencil, Trash2, Plus, Eye, Save, } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { required } from "@/shared/utils/validators.js";
import { computed } from "vue";

const { t } = useI18n();

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

const availableProducts = ref([]);
async function loadProducts() {
    const response = await fetch(props.productsPath, { headers: { "X-Requested-With": "XMLHttpRequest" } });
    const data = await response.json();
    if (data.ok) availableProducts.value = data.items;
}
onMounted(loadProducts);

function emptyForm() {
    return { productId: "", slug: "", marketingTitle: "", marketingDescription: "", isVisibleOnShop: true, seoTitle: "", seoDescription: "", featuredImageId: null, featuredImageUrl: null };
}

function makeImageRef(form) {
    return computed({
        get: () => ({ id: form.value.featuredImageId, url: form.value.featuredImageUrl }),
        set: (v) => {
            form.value.featuredImageId = v.id;
            form.value.featuredImageUrl = v.url;
        },
    });
}

function slugify(value) {
    return (value ?? "")
        .toLowerCase()
        .normalize("NFD").replace(/[̀-ͯ]/g, "")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");
}

// --- Create ---
const showCreate = ref(false);
const newListing = ref(emptyForm());
const newListingImage = makeImageRef(newListing);
const { errors: createErrors, validate: validateCreate, clearErrors: clearCreate, setErrors: setCreateErrors } = useForm();
const { loading: createLoading, request: createRequest } = useApiRequest();

function openCreate() {
    newListing.value = emptyForm();
    clearCreate();
    showCreate.value = true;
}

function onProductChange(product, form) {
    if (product && !form.slug) {
        form.slug = slugify(product.name);
    }
}

async function submitCreate() {
    if (!validateCreate({
        productId: () => required(t("admin.ecommerce.listings.errors.product_required"))(newListing.value.productId),
        slug: () => required(t("admin.ecommerce.listings.errors.slug_required"))(newListing.value.slug),
    })) return;

    const data = await createRequest(props.createPath, newListing.value);
    if (!data) return;
    if (data.success) {
        showCreate.value = false;
        toast.success(t("admin.ecommerce.listings.created"));
        reset();
        loadProducts();
    } else {
        if (data.errors?._global) toast.error(data.errors._global);
        setCreateErrors(data.errors ?? {});
    }
}

// --- Edit ---
const showEdit = ref(false);
const editingListing = ref(null);
const editForm = ref(emptyForm());
const editFormImage = makeImageRef(editForm);
const { errors: editErrors, validate: validateEdit, clearErrors: clearEdit, setErrors: setEditErrors } = useForm();
const { loading: editLoading, request: editRequest } = useApiRequest();

function openEdit(listing) {
    editingListing.value = listing;
    editForm.value = {
        productId: listing.product.id,
        slug: listing.slug,
        marketingTitle: listing.marketingTitle ?? "",
        marketingDescription: listing.marketingDescription ?? "",
        isVisibleOnShop: listing.isVisibleOnShop,
        seoTitle: listing.seoTitle ?? "",
        seoDescription: listing.seoDescription ?? "",
        featuredImageId: listing.featuredImage?.id ?? null,
        featuredImageUrl: listing.featuredImage?.url ?? null,
    };
    clearEdit();
    showEdit.value = true;
}

async function submitEdit() {
    if (!validateEdit({
        slug: () => required(t("admin.ecommerce.listings.errors.slug_required"))(editForm.value.slug),
    })) return;

    const url = props.updatePath.replace("__id__", editingListing.value.id);
    const data = await editRequest(url, editForm.value);
    if (!data) return;
    if (data.success) {
        showEdit.value = false;
        toast.success(t("admin.ecommerce.listings.updated"));
        reset();
    } else {
        if (data.errors?._global) toast.error(data.errors._global);
        setEditErrors(data.errors ?? {});
    }
}

// --- Delete ---
const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath,
    () => { reset(); loadProducts(); },
    'admin.ecommerce.listings.deleted',
);

function formatPrice(product) {
    if (product.price === null || product.price === undefined) return "—";
    try {
        return new Intl.NumberFormat(undefined, { style: "currency", currency: product.currency || "EUR" }).format(product.price);
    } catch {
        return `${product.price} ${product.currency || ""}`;
    }
}
</script>

<template>
    <div class="space-y-4">
        <!-- Toolbar -->
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('admin.ecommerce.listings.searchPlaceholder')"
                v-on:search="onSearch"
            />
            <AppButton variant="primary" size="md" class="w-full sm:w-auto" v-on:click="openCreate">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.ecommerce.listings.add') }}
            </AppButton>
        </div>

        <!-- Desktop table -->
        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 border-b border-line">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.ecommerce.listings.title') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.ecommerce.listings.slug') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('admin.ecommerce.listings.price') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.ecommerce.listings.visibility') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="listing in items" :key="listing.id" class="hover:bg-surface-2/50 transition-colors">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded bg-surface-2 overflow-hidden shrink-0 flex items-center justify-center">
                                    <img v-if="listing.displayImage" :src="listing.displayImage.url" :alt="listing.displayImage.alt ?? listing.displayTitle" class="w-full h-full object-cover">
                                    <span v-else class="text-muted text-xs">—</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-primary truncate">{{ listing.displayTitle }}</div>
                                    <div class="text-xs font-mono text-muted">{{ listing.product.sku }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3 font-mono text-xs text-secondary">/{{ listing.slug }}</td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ formatPrice(listing.product) }}</td>
                        <td class="px-6 py-3">
                            <AppBadge :color="listing.isVisibleOnShop ? 'emerald' : 'slate'">
                                {{ t(listing.isVisibleOnShop ? 'admin.ecommerce.listings.visible' : 'admin.ecommerce.listings.hidden') }}
                            </AppBadge>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="showPath" color="sky" :href="showPath.replace('__id__', listing.id)"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(listing)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(listing)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items?.length">
                        <td :colspan="5" class="px-6 py-8 text-center text-sm text-muted">{{ t('admin.ecommerce.listings.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile cards -->
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
                    <p class="text-sm text-secondary">{{ formatPrice(listing.product) }}</p>
                    <div class="flex items-center gap-0.5">
                        <AppIconButton v-if="showPath" color="sky" :href="showPath.replace('__id__', listing.id)"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(listing)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(listing)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>
            </div>
            <p v-if="!items?.length" class="py-8 text-center text-sm text-muted">{{ t('admin.ecommerce.listings.empty') }}</p>
        </div>

        <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />

        <!-- Create modal -->
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
                    <option v-for="product in availableProducts" :key="product.id" :value="product.id">{{ product.name }} ({{ product.sku }})</option>
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

        <!-- Edit modal -->
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

        <!-- Delete confirm -->
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
