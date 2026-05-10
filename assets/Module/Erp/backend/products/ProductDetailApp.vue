<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useDetailDelete } from "@/shared/composables/form/useDetailDelete.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { useLoadMore } from "@/shared/composables/api/useLoadMore.js";
import { useProductsOptions } from "./composables/useProductsOptions.js";
import { useProductDetailEdit, useProductActivityLabels } from "./composables/useProductDetailEdit.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppLoadMore from "@/shared/components/nav/AppLoadMore.vue";
import AppImage from "@/shared/components/display/AppImage.vue";
import { Pencil, Trash2, Package, Save, X } from "lucide-vue-next";
import { formatProductPrice } from "@/shared/utils/format/formatPrice.js";
import { CURRENCY_OPTIONS, symbolFor } from "@/shared/utils/format/currencies.js";

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

const { STATUS_OPTIONS, STATUS_TONE, TYPE_OPTIONS, TYPE_TONE } = useProductsOptions();

const product = ref({ ...props.product });

const { showEdit, editForm, editFormImage, editErrors, editLoading, submitEdit } = useProductDetailEdit(props.updatePath, product);
const actionLabel = useProductActivityLabels();

// --- Activity (load more) ---
const { items: activityItems, hasMore: hasMoreActivity, loading: loadingActivity, loadMore: loadMoreActivity } =
    useLoadMore(props.activityPath, props.activity);

// --- Delete ---
const { showDelete, loading: deleteLoading, submit: doDelete } = useDetailDelete(props.deletePath, props.backPath);
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-surface border border-line rounded-lg p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-6">
                    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                        <div v-if="product.image" class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg overflow-hidden shrink-0">
                            <AppImage :src="product.image.url" :alt="product.image.alt ?? product.name" object-fit="cover" />
                        </div>
                        <div v-else class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg bg-accent-600/20 text-accent-400 flex items-center justify-center shrink-0">
                            <Package class="w-5 h-5 sm:w-6 sm:h-6" :stroke-width="2" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg sm:text-xl font-bold text-primary break-words">{{ product.name }}</h2>
                            <p class="text-xs font-mono text-muted mt-0.5 break-all">{{ product.reference }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between sm:justify-end gap-2 sm:shrink-0">
                        <AppBadge :color="STATUS_TONE[product.status] ?? 'slate'">
                            {{ t(`backend.erp.products.status.${product.status}`) }}
                        </AppBadge>
                        <div class="flex items-center gap-1">
                            <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="showEdit = true"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="showDelete = true"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        </div>
                    </div>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.erp.products.price') }}</dt>
                        <dd class="text-primary font-medium">{{ formatProductPrice(product) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.erp.products.currency') }}</dt>
                        <dd class="text-primary">{{ product.currency }} <span class="text-muted">({{ product.currencySymbol }})</span></dd>
                    </div>
                    <div v-if="product.description" class="sm:col-span-2">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.erp.products.description') }}</dt>
                        <dd class="text-secondary text-sm whitespace-pre-wrap break-words">{{ product.description }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-primary uppercase tracking-wide">{{ t('backend.erp.activity.title') }}</h3>

            <div v-if="!activityItems.length" class="text-sm text-muted">{{ t('backend.erp.activity.empty') }}</div>

            <template v-else>
                <ol class="relative border-l border-line ml-3 space-y-6">
                    <li v-for="event in activityItems" :key="event.id" class="ml-4">
                        <div class="absolute w-2.5 h-2.5 bg-accent-600 rounded-full -left-1.5 border-2 border-bg" />
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

        <AppModal :show="showEdit" :title="t('backend.erp.products.edit', { name: product.name })" :closeable="false" v-on:close="showEdit = false">
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('backend.erp.products.name')"
                    :placeholder="t('backend.erp.products.namePlaceholder')"
                    :error="editErrors.name"
                    required
                />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppInput
                        v-model="editForm.reference"
                        :label="t('backend.erp.products.reference')"
                        :placeholder="t('backend.erp.products.referenceAutoPlaceholder')"
                        :error="editErrors.reference"
                    />
                    <AppSelect v-model="editForm.status" :label="t('backend.erp.products.statusLabel')">
                        <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label() }}</option>
                    </AppSelect>
                </div>
                <AppSelect v-model="editForm.type" :label="t('backend.erp.products.typeLabel')">
                    <option v-for="opt in TYPE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label() }}</option>
                </AppSelect>
                <div class="grid grid-cols-[1fr_8rem] gap-3">
                    <AppInput
                        v-model="editForm.price"
                        type="number"
                        min="0"
                        step="0.01"
                        :label="`${t('backend.erp.products.price')} (${symbolFor(editForm.currency)})`"
                        :placeholder="t('backend.erp.products.pricePlaceholder')"
                        :error="editErrors.price ?? editErrors.priceCents"
                    />
                    <AppSelect v-model="editForm.currency" :label="t('backend.erp.products.currency')">
                        <option v-for="opt in CURRENCY_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.value }} — {{ opt.symbol }}</option>
                    </AppSelect>
                </div>
                <AppTextarea v-model="editForm.description" :rows="3" :placeholder="t('backend.erp.products.descriptionPlaceholder')" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal :show="showDelete" max-width="sm" v-on:close="showDelete = false">
            <p class="text-sm text-primary">{{ t('backend.erp.products.deleteConfirm', { name: product.name }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.erp.products.deleteWarning') }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="showDelete = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
