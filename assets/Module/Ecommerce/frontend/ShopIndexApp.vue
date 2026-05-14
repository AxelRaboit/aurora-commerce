<script setup>
import { useI18n } from "vue-i18n";
import AppImage from "@/shared/components/display/AppImage.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import { formatMoney } from "@ecommerce/shared/formatMoney.js";
import { ImageOff } from "lucide-vue-next";
import { useShopSearch } from "./composables/useShopSearch.js";

const { t } = useI18n();

const props = defineProps({
    listings: { type: Array, default: () => [] },
    initialPage: { type: Number, default: 1 },
    initialTotalPages: { type: Number, default: 1 },
    initialTotal: { type: Number, default: 0 },
    locale: { type: String, default: "fr" },
    productPathBase: { type: String, required: true },
    indexPath: { type: String, required: true },
    searchPath: { type: String, required: true },
});

const { query, listings, page, totalPages, loading, onSearch, goToPage } = useShopSearch(props);

function productUrl(slug) {
    return `${props.productPathBase}/${slug}`;
}
</script>

<template>
    <section>
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-6">
            <h1 class="text-3xl font-bold flex-1">{{ t('frontend.shop.title') }}</h1>
            <div class="w-full sm:w-72">
                <AppSearchInput
                    :model-value="query"
                    :placeholder="t('frontend.shop.search_placeholder')"
                    v-on:search="onSearch"
                />
            </div>
        </div>

        <div v-if="loading" class="text-muted text-sm py-8 text-center">{{ t('shared.common.loadMore') }}…</div>

        <AppNoData v-else-if="!listings.length" :message="query ? t('frontend.shop.no_results') : t('frontend.shop.empty')" />

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <article
                v-for="listing in listings"
                :key="listing.id"
                class="bg-surface border border-line/60 rounded-xl overflow-hidden hover:border-accent transition-colors"
            >
                <a :href="productUrl(listing.slug)" class="block">
                    <div class="aspect-square bg-surface-2 overflow-hidden">
                        <AppImage
                            v-if="listing.displayImage"
                            :src="listing.displayImage.url"
                            :alt="listing.displayImage.alt || listing.displayTitle"
                            object-fit="cover"
                            loading="lazy"
                        />
                        <div v-else class="w-full h-full flex flex-col items-center justify-center gap-1 text-muted text-xs">
                            <ImageOff class="w-6 h-6 opacity-40" :stroke-width="1.5" />
                            {{ t('frontend.shop.no_image') }}
                        </div>
                    </div>
                    <div class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <h2 class="text-lg font-semibold text-primary">{{ listing.displayTitle }}</h2>
                            <span v-if="listing.product.type === 'digital'" class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-accent-500/15 text-accent-400">
                                {{ t('frontend.shop.type.digital') }}
                            </span>
                            <span v-else-if="listing.product.type === 'service'" class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-violet-500/15 text-violet-400">
                                {{ t('frontend.shop.type.service') }}
                            </span>
                        </div>
                        <p v-if="listing.product.price !== null" class="text-base font-bold text-accent">
                            {{ formatMoney(listing.product.price, listing.product.currencySymbol) }}
                        </p>
                        <p v-if="listing.product.stockTracked && !listing.product.inStock" class="text-xs text-rose-400 font-medium">
                            {{ t('frontend.shop.out_of_stock') }}
                        </p>
                        <p v-else-if="listing.product.isLowStock" class="text-xs text-amber-400">
                            {{ t('frontend.shop.low_stock', { count: listing.product.stockQuantity }) }}
                        </p>
                    </div>
                </a>
            </article>
        </div>

        <div class="mt-8">
            <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />
        </div>
    </section>
</template>
