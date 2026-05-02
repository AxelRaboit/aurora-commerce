<script setup>
import { computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { useAdminAudit } from "@core/admin/dev/composables/useAdminAudit.js";

const { t } = useI18n();
const { formatDateTime } = useDateFormat();

const props = defineProps({
    auditPath: { type: String, required: true },
    initialData: { type: Object, default: null },
});

const { data, load, module, setModule } = useAdminAudit(props.auditPath, props.initialData);

const moduleOptions = computed(() =>
    (data.value?.modules ?? []).map((name) => ({
        value: name,
        label: t(`admin.modules.${name}`),
    })),
);

onMounted(() => {
    if (!data.value?.items?.length) load();
});
</script>

<template>
    <div class="space-y-3">
        <div v-if="data?.modules?.length" class="max-w-xs">
            <AppMultiselect
                :model-value="module"
                :options="moduleOptions"
                :label="t('admin.audit.module')"
                :placeholder="t('shared.common.all')"
                allow-empty
                v-on:update:model-value="setModule"
            />
        </div>
        <p v-if="!data?.items?.length" class="py-8 text-center text-sm text-muted">{{ t('admin.audit.empty') }}</p>
        <div v-else class="bg-surface border border-line rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 border-b border-line">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ t('admin.audit.action') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary hidden sm:table-cell">{{ t('admin.audit.module') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary hidden md:table-cell">{{ t('admin.audit.user') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ t('admin.audit.date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="log in data.items" :key="log.id" class="hover:bg-surface-2/50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="text-primary text-sm font-medium">{{ t(`admin.audit.actions.${log.module}.${log.action}`) }}</span>
                            <span v-if="log.entityType" class="ml-2 text-muted text-xs">{{ log.entityType }} #{{ log.entityId }}</span>
                            <span v-if="log.data?.name" class="ml-2 text-secondary text-xs truncate">— {{ log.data.name }}</span>
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-surface-2 text-secondary">{{ t(`admin.modules.${log.module}`) }}</span>
                        </td>
                        <td class="px-4 py-3 text-secondary text-xs hidden md:table-cell">
                            <template v-if="log.userName">{{ log.userName }}</template>
                            <template v-if="log.userName && log.userEmail"> · </template>
                            <span v-if="log.userEmail" class="text-muted">{{ log.userEmail }}</span>
                            <template v-if="!log.userName && !log.userEmail">—</template>
                        </td>
                        <td class="px-4 py-3 text-secondary text-xs">{{ formatDateTime(log.createdAt) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
