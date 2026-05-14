<script setup>
import { useI18n } from "vue-i18n";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import ShopListingCard from "@ecommerce/frontend/ShopListingCard.vue";
import { useShopSearch } from "./composables/useShopSearch.js";

const { t } = useI18n();

const props = defineProps({
    listings: { type: Array, default: () => [] },
    initialPage: { type: Number, default: 1 },
    initialTotalPages: { type: Number, default: 1 },
    initialTotal: { type: Number, default: 0 },
    locale: { type: String, default: "fr" },
    rootCategories: { type: Array, default: () => [] },
    availableTags: { type: Array, default: () => [] },
    productPathBase: { type: String, required: true },
    categoryPathBase: { type: String, default: "" },
    tagPathBase: { type: String, default: "" },
    indexPath: { type: String, required: true },
    searchPath: { type: String, required: true },
});

const { query, listings, page, totalPages, loading, onSearch, goToPage } = useShopSearch(props);

function productUrl(slug) {
    return `${props.productPathBase}/${slug}`;
}

function categoryUrl(slug) {
    return `${props.categoryPathBase}/${slug}`;
}

function tagUrl(slug) {
    return `${props.tagPathBase}/${slug}`;
}
</script>

<template>
    <section>
        <nav v-if="rootCategories.length" class="mb-4" :aria-label="t('frontend.shop.categories')">
            <h2 class="text-sm font-semibold text-secondary uppercase tracking-wide mb-3">{{ t('frontend.shop.categories') }}</h2>
            <ul class="flex flex-wrap gap-2">
                <li v-for="category in rootCategories" :key="category.slug">
                    <a
                        :href="categoryUrl(category.slug)"
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-surface-2 text-secondary border border-line/60 hover:bg-surface-3 hover:text-primary transition-colors"
                    >{{ category.name }}</a>
                </li>
            </ul>
        </nav>

        <nav v-if="availableTags.length" class="mb-6" :aria-label="t('frontend.shop.tags')">
            <h2 class="text-sm font-semibold text-secondary uppercase tracking-wide mb-3">{{ t('frontend.shop.tags') }}</h2>
            <ul class="flex flex-wrap gap-2">
                <li v-for="tag in availableTags" :key="tag.slug">
                    <a
                        :href="tagUrl(tag.slug)"
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white hover:opacity-90 transition-opacity"
                        :style="{ backgroundColor: tag.color }"
                    >{{ tag.name }}</a>
                </li>
            </ul>
        </nav>

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
            <ShopListingCard
                v-for="listing in listings"
                :key="listing.id"
                :listing="listing"
                :product-url="productUrl(listing.slug)"
            />
        </div>

        <div class="mt-8">
            <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />
        </div>
    </section>
</template>
