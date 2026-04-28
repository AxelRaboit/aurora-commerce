<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useApiRequest } from "@/shared/composables/useApiRequest.js";
import { useForm } from "@/shared/composables/useForm.js";
import AppButton from "@/shared/components/AppButton.vue";
import AppIconButton from "@/shared/components/AppIconButton.vue";
import AppInput from "@/shared/components/AppInput.vue";
import AppTextarea from "@/shared/components/AppTextarea.vue";
import AppToggle from "@/shared/components/AppToggle.vue";
import AppLink from "@/shared/components/AppLink.vue";
import AppBadge from "@/shared/components/AppBadge.vue";
import AppModal from "@/shared/components/AppModal.vue";
import AppModalFooter from "@/shared/components/AppModalFooter.vue";
import AppImagePickerField from "@/shared/components/AppImagePickerField.vue";
import AppImage from "@/shared/components/AppImage.vue";
import { Pencil, Trash2, ShoppingBag, ExternalLink, Save, } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { required } from "@/shared/utils/validators.js";
import { formatProductPrice } from "@/shared/utils/formatPrice.js";

const { t } = useI18n();

const props = defineProps({
    listing: { type: Object, required: true },
    backPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

const listing = ref({ ...props.listing });
const frontUrl = computed(() => `/${(document.documentElement.lang || "fr").slice(0, 2)}/shop/${listing.value.slug}`);

// --- Edit ---
const showEdit = ref(false);
const editForm = ref({
    productId: listing.value.product.id,
    slug: listing.value.slug,
    marketingTitle: listing.value.marketingTitle ?? "",
    marketingDescription: listing.value.marketingDescription ?? "",
    isVisibleOnShop: listing.value.isVisibleOnShop,
    seoTitle: listing.value.seoTitle ?? "",
    seoDescription: listing.value.seoDescription ?? "",
    featuredImageId: listing.value.featuredImage?.id ?? null,
    featuredImageUrl: listing.value.featuredImage?.url ?? null,
});

const featuredImageValue = computed({
    get: () => ({ id: editForm.value.featuredImageId, url: editForm.value.featuredImageUrl }),
    set: (v) => {
        editForm.value.featuredImageId = v.id;
        editForm.value.featuredImageUrl = v.url;
    },
});
const { errors: editErrors, validate: validateEdit, setErrors: setEditErrors } = useForm();
const { loading: editLoading, request: editRequest } = useApiRequest();

async function submitEdit() {
    if (!validateEdit({
        slug: () => required(t("admin.ecommerce.listings.errors.slug_required"))(editForm.value.slug),
    })) return;

    const data = await editRequest(props.updatePath, editForm.value);
    if (!data) return;
    if (data.success) {
        listing.value = { ...listing.value, ...(data.listing ?? {}) };
        showEdit.value = false;
        toast.success(t("shared.common.saved"));
    } else {
        if (data.errors?._global) toast.error(data.errors._global);
        setEditErrors(data.errors ?? {});
    }
}

// --- Delete ---
const showDelete = ref(false);
const { loading: deleteLoading, request: deleteRequest } = useApiRequest();
async function doDelete() {
    const data = await deleteRequest(props.deletePath, {});
    if (data?.success) window.location.href = props.backPath;
}
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-surface border border-line rounded-lg p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-6">
                    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                        <div v-if="listing.displayImage" class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg overflow-hidden shrink-0">
                            <AppImage :src="listing.displayImage.url" :alt="listing.displayImage.alt ?? listing.displayTitle" object-fit="cover" />
                        </div>
                        <div v-else class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg bg-accent-600/20 text-accent-400 flex items-center justify-center shrink-0">
                            <ShoppingBag class="w-5 h-5 sm:w-6 sm:h-6" :stroke-width="2" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg sm:text-xl font-bold text-primary break-words">{{ listing.displayTitle }}</h2>
                            <p class="text-xs font-mono text-muted mt-0.5 break-all">{{ listing.product.sku }} · /{{ listing.slug }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between sm:justify-end gap-2 sm:shrink-0">
                        <AppBadge :color="listing.isVisibleOnShop ? 'emerald' : 'slate'">
                            {{ t(listing.isVisibleOnShop ? 'admin.ecommerce.listings.visible' : 'admin.ecommerce.listings.hidden') }}
                        </AppBadge>
                        <div class="flex items-center gap-1">
                            <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="showEdit = true"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="showDelete = true"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        </div>
                    </div>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.ecommerce.listings.price') }}</dt>
                        <dd class="text-primary font-medium">{{ formatProductPrice(listing.product) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.ecommerce.listings.frontUrl') }}</dt>
                        <dd>
                            <AppLink :href="frontUrl" target="_blank" class="text-accent-400 hover:underline inline-flex items-center gap-1 text-sm">
                                {{ frontUrl }}
                                <ExternalLink class="w-3.5 h-3.5" :stroke-width="2" />
                            </AppLink>
                        </dd>
                    </div>
                    <div v-if="listing.marketingDescription" class="sm:col-span-2">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.ecommerce.listings.marketingDescription') }}</dt>
                        <dd class="text-secondary text-sm whitespace-pre-wrap break-words">{{ listing.marketingDescription }}</dd>
                    </div>
                    <div v-if="listing.seoTitle || listing.seoDescription" class="sm:col-span-2 pt-3 border-t border-line">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('admin.ecommerce.listings.seo') }}</dt>
                        <dd class="space-y-1">
                            <p v-if="listing.seoTitle" class="text-primary text-sm">{{ listing.seoTitle }}</p>
                            <p v-if="listing.seoDescription" class="text-secondary text-sm">{{ listing.seoDescription }}</p>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="space-y-4">
            <div v-if="listing.displayImage" class="bg-surface border border-line/60 rounded-lg overflow-hidden">
                <div class="aspect-square bg-surface-2">
                    <AppImage :src="listing.displayImage.url" :alt="listing.displayImage.alt ?? listing.displayTitle" object-fit="cover" />
                </div>
                <p v-if="listing.featuredImage" class="text-xs text-muted px-3 py-2 border-t border-line">{{ t('admin.ecommerce.listings.featuredImageHint') }}</p>
                <p v-else class="text-xs text-muted px-3 py-2 border-t border-line">{{ t('admin.ecommerce.listings.productImageHint') }}</p>
            </div>

            <h3 class="text-sm font-semibold text-primary uppercase tracking-wide">{{ t('admin.ecommerce.listings.linkedProduct') }}</h3>
            <div class="bg-surface border border-line/60 rounded-lg p-4 space-y-2">
                <p class="font-medium text-primary">{{ listing.product.name }}</p>
                <p class="text-xs font-mono text-muted">{{ listing.product.sku }}</p>
                <p class="text-sm text-secondary">{{ formatProductPrice(listing.product) }}</p>
                <AppBadge :color="listing.product.status === 'active' ? 'emerald' : listing.product.status === 'draft' ? 'amber' : 'slate'">
                    {{ t(`admin.erp.products.status.${listing.product.status}`) }}
                </AppBadge>
            </div>
        </div>

        <AppModal :show="showEdit" v-on:close="showEdit = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.ecommerce.listings.edit', { name: listing.displayTitle }) }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput v-model="editForm.slug" :label="t('admin.ecommerce.listings.slug')" :error="editErrors.slug" required />
                <AppInput v-model="editForm.marketingTitle" :label="t('admin.ecommerce.listings.marketingTitle')" />
                <AppTextarea v-model="editForm.marketingDescription" :rows="4" />
                <AppImagePickerField
                    v-model="featuredImageValue"
                    :label="t('admin.ecommerce.listings.featuredImage')"
                    :hint="t('admin.ecommerce.listings.featuredImageOverrideHint')"
                />
                <AppInput v-model="editForm.seoTitle" :label="t('admin.ecommerce.listings.seoTitle')" />
                <AppTextarea v-model="editForm.seoDescription" :rows="2" :placeholder="t('admin.ecommerce.listings.seoDescription')" />
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

        <AppModal :show="showDelete" max-width="sm" v-on:close="showDelete = false">
            <p class="text-sm text-primary">{{ t('admin.ecommerce.listings.deleteConfirm', { name: listing.displayTitle }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.ecommerce.listings.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="showDelete = false">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
