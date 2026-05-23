<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { Trash2, X, Sparkles } from "lucide-vue-next";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useCategorizationRuleUpdate } from "./composables/useCategorizationRuleUpdate.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";

const props = defineProps({
    categoriesByWallet: { type: Object, required: true },
    rules: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    listPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();

const { items, loading, page, totalPages, search: searchInput, onSearch, goToPage, reload } = useListPage(
    props.listPath,
    {
        initialSearch: props.search,
        initialData: props.rules,
    },
);

const allCategoryOptions = computed(() => {
    const opts = [];
    for (const [walletId, cats] of Object.entries(props.categoriesByWallet)) {
        for (const c of cats) {
            opts.push({ value: c.id, label: `${c.name}` });
        }
    }
    return opts;
});

const { setCategory } = useCategorizationRuleUpdate(props.updatePath, () => reload());

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath,
    () => reload(),
    "personal_finance.categorization.deleted",
);
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('personal_finance.categorization.search_placeholder')"
                v-on:search="onSearch"
            />
        </AppListToolbar>

        <p class="text-sm text-muted">{{ t("personal_finance.categorization.intro") }}</p>

        <section v-if="!items?.length" class="bg-surface border border-line rounded-lg p-8 text-center text-muted text-sm">
            <Sparkles class="inline w-5 h-5 mr-1" :stroke-width="1.5" />
            {{ t("personal_finance.categorization.empty") }}
        </section>

        <div v-else class="relative space-y-4">
            <div class="bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.categorization.fields.pattern") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.categorization.fields.category") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.categorization.fields.wallet") }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.categorization.fields.hits") }}</th>
                            <slot name="extra-headers" />
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-for="rule in items" :key="rule.id" class="group hover:bg-surface-2/40 transition-colors">
                            <td class="px-6 py-3 font-mono text-xs">{{ rule.pattern }}</td>
                            <td class="px-6 py-3 w-64">
                                <AppMultiselect
                                    :model-value="rule.categoryId"
                                    :options="allCategoryOptions"
                                    :allow-empty="false"
                                    v-on:update:model-value="(val) => setCategory(rule, val)"
                                />
                            </td>
                            <td class="px-6 py-3 text-muted text-xs">{{ rule.walletName }}</td>
                            <td class="px-6 py-3 text-right font-mono">{{ rule.hits }}</td>
                            <slot name="extra-cells" :rule="rule" />
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(rule)">
                                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:change="goToPage" />
            <AppLoader :active="loading" />
        </div>

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">
                {{ t("personal_finance.categorization.delete_confirm", { pattern: pendingDelete?.pattern ?? '' }) }}
            </p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
