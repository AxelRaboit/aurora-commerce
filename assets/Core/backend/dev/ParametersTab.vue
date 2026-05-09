<script setup>
import { onMounted } from "vue";
import { useI18n } from "vue-i18n";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import { Save } from "lucide-vue-next";
import { useParameters } from "@core/backend/dev/composables/useParameters.js";

const { t } = useI18n();

const props = defineProps({
    parametersPath: { type: String, required: true },
    parameterUpdatePath: { type: String, required: true },
    initialData: { type: Object, default: null },
    initialSearch: { type: String, default: "" },
});

const parameters = useParameters(
    props.parametersPath,
    props.parameterUpdatePath,
    props.initialData,
    props.initialSearch,
);

onMounted(() => {
    if (!parameters.items.value?.length) parameters.load();
});
</script>

<template>
    <div class="space-y-3">
        <p class="text-sm text-secondary">{{ t('backend.parameters.intro') }}</p>
        <div>
            <AppSearchInput
                v-model="parameters.searchInput.value"
                :placeholder="t('backend.parameters.searchPlaceholder')"
                v-on:search="parameters.performSearch"
            />
        </div>

        <div class="sm:hidden space-y-3">
            <p v-if="!parameters.items.value?.length" class="py-8 text-center text-sm text-muted">{{ t('backend.parameters.empty') }}</p>
            <div v-for="parameter in parameters.items.value" :key="parameter.key" class="bg-surface border border-line rounded-lg p-4 space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-mono text-sm text-accent-400 font-medium break-all">{{ parameter.key }}</p>
                        <p v-if="parameter.label && parameter.label !== parameter.key" class="text-xs text-secondary mt-0.5">{{ parameter.label }}</p>
                    </div>
                    <AppBadge v-if="parameter.group" color="gray" class="shrink-0">{{ parameter.group }}</AppBadge>
                </div>
                <div v-if="parameters.editingKey.value === parameter.key" class="space-y-2">
                    <AppInput v-model="parameters.editingValue.value" v-on:keyup.enter="parameters.saveEdit(parameter)" v-on:keyup.esc="parameters.cancelEdit" />
                    <div class="flex gap-2">
                        <AppButton
                            variant="primary"
                            size="md"
                            class="flex-1"
                            :loading="parameters.editSaving.value"
                            v-on:click="parameters.saveEdit(parameter)"
                        >
                            <Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}
                        </AppButton>
                        <AppButton variant="ghost" size="md" class="flex-1" v-on:click="parameters.cancelEdit">{{ t('shared.common.cancel') }}</AppButton>
                    </div>
                </div>
                <button v-else type="button" class="text-left w-full px-2 py-1 rounded-md text-primary hover:bg-surface-2 transition-colors text-sm font-medium break-all" v-on:click="parameters.startEdit(parameter)">
                    <span v-if="parameter.value !== null && parameter.value !== ''">{{ parameter.value }}</span>
                    <span v-else class="text-muted italic">-</span>
                </button>
                <p v-if="parameter.description" class="text-xs text-secondary">{{ parameter.description }}</p>
            </div>
        </div>

        <div class="hidden sm:block bg-surface border border-line rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted w-1/3">{{ t('backend.parameters.key') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted w-1/4">{{ t('backend.parameters.value') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.parameters.description') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="parameter in parameters.items.value" :key="parameter.key" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-5 py-3 align-top w-1/3">
                            <p class="font-mono text-sm text-accent-400 font-medium break-all">{{ parameter.key }}</p>
                            <p v-if="parameter.label && parameter.label !== parameter.key" class="text-xs text-secondary mt-0.5">{{ parameter.label }}</p>
                            <AppBadge v-if="parameter.group" color="gray" class="mt-1">{{ parameter.group }}</AppBadge>
                        </td>
                        <td class="px-5 py-3 align-top w-1/4">
                            <div v-if="parameters.editingKey.value === parameter.key" class="space-y-2">
                                <AppInput v-model="parameters.editingValue.value" v-on:keyup.enter="parameters.saveEdit(parameter)" v-on:keyup.esc="parameters.cancelEdit" />
                                <div class="flex gap-2">
                                    <AppButton variant="primary" size="md" :loading="parameters.editSaving.value" v-on:click="parameters.saveEdit(parameter)"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                                    <AppButton variant="ghost" size="md" v-on:click="parameters.cancelEdit">{{ t('shared.common.cancel') }}</AppButton>
                                </div>
                            </div>
                            <button v-else type="button" class="text-left w-full px-2 py-1 rounded-md text-primary hover:bg-surface-2 transition-colors font-medium" v-on:click="parameters.startEdit(parameter)">
                                <span v-if="parameter.value !== null && parameter.value !== ''">{{ parameter.value }}</span>
                                <span v-else class="text-muted italic">-</span>
                            </button>
                        </td>
                        <td class="px-5 py-3 align-top text-sm text-secondary hidden md:table-cell max-w-md">{{ parameter.description }}</td>
                    </tr>
                    <tr v-if="!parameters.items.value?.length">
                        <td colspan="3" class="px-5 py-8 text-center text-sm text-muted">{{ t('backend.parameters.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <AppPagination
            v-if="parameters.totalPages.value > 1"
            :page="parameters.page.value"
            :total-pages="parameters.totalPages.value"
            v-on:change="parameters.goToPage"
        />
    </div>
</template>
